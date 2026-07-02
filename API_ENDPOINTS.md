# API Endpoints

## Overview

The API is split into two files:
- **`api.php`** — Items, Reports, Idol Entities, Type Categories, Budgets, Backups
- **`api_users.php`** — User management

All responses are JSON (`Content-Type: application/json`).
All endpoints require an active login session (returns `401` if unauthenticated).

---

## Authentication & CSRF

- Authentication is **session-based**. Log in via `login.php` first.
- All **POST** requests must include a CSRF token — either as:
  - A form field: `csrf_token=<token>`
  - An HTTP header: `X-CSRF-Token: <token>`
- The CSRF token is available from the PHP helper `csrfToken()` and embedded in every page.
- Backup and user-delete actions are **admin only** (returns `403` otherwise).

### Error Response Format
```json
{ "error": "Human-readable error message" }
```

---

## Items (`api.php`)

### `list` — GET

List items with pagination, filters, and sorting.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | int | 1 | Page number |
| `per_page` | int | 20 | Items per page (max 200) |
| `idol[]` | string[] | — | Filter by one or more idol names |
| `type[]` | string[] | — | Filter by one or more type names |
| `event_id[]` | int[] | — | Filter by one or more event IDs |
| `search` | string | — | Full-text search on `title` |
| `date_field` | `order_date`\|`event_date` | `order_date` | Which column `date_from`/`date_to` apply to |
| `date_from` | YYYY-MM-DD | — | Date range start (on `date_field`) |
| `date_to` | YYYY-MM-DD | — | Date range end (on `date_field`) |
| `sort` | string | `order_date` | Sort column: `order_date`, `event_date`, `title`, `idol`, `type`, `price_per_qty`, `qty`, `id` |
| `dir` | `asc`\|`desc` | `desc` | Sort direction |

**Response:**
```json
{
  "data": [ { "id": 1, "order_date": "2024-01-15", "event_date": "", "title": "Photo Card Set", "idol": "Member A", "type": "Photo", "price_per_qty": 350.0, "qty": 2, "total_price": 700.0 } ],
  "total": 42,
  "page": 1,
  "per_page": 20,
  "total_pages": 3,
  "summary": { "total_price": 15000.0, "total_qty": 38 }
}
```

---

### `get` — GET

Get a single item by ID.

**Query Parameters:** `id` (int, required)

**Response:**
```json
{ "data": { "id": 1, "order_date": "...", ... } }
```

---

### `create` — POST

Create a new item. Uses the **hybrid idol resolution** policy:

1. If `idol_id` is provided, the item is linked to that entity (text snapshot is overwritten with the entity name).
2. Otherwise, the text `idol` is resolved against `idol_entities`:
   - Unique match → `idol_id` is auto-filled.
   - Ambiguous match → HTTP **409** with `candidates` array.
   - No match → `idol_id` stays `NULL` (item appears as "Unassigned" in reports).

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `order_date` | YYYY-MM-DD | Purchase date |
| `event_date` | YYYY-MM-DD | Event date (can be empty) |
| `title` | string | Item name |
| `idol` | string | Idol / member name (used when `idol_id` is omitted) |
| `idol_id` | int | Optional — explicit reference to `idol_entities.id` (preferred) |
| `type` | string | Item type |
| `price_per_qty` | float | Price per unit |
| `qty` | int | Quantity |
| `csrf_token` | string | CSRF token |

**Response (success):**
```json
{ "success": true, "id": 43 }
```

**Response (ambiguous name, HTTP 409):**
```json
{
  "error": "Ambiguous idol name — please specify idol_id",
  "name": "Yuna",
  "candidates": [
    { "id": 42, "name": "Yuna", "display_hint": "ITZY",  "display": "Yuna [ITZY]" },
    { "id": 87, "name": "Yuna", "display_hint": "AKB48", "display": "Yuna [AKB48]" }
  ]
}
```

---

### `update` — POST

Update an existing item.

**POST Body:** Same as `create`, plus `id` (int, required).

**Response:**
```json
{ "success": true }
```

---

### `delete` — POST

Delete an item.

**POST Body:** `id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

### `filters` — GET

Get distinct idol and type values for filter dropdowns.

**Response:**
```json
{ "idols": ["Member A", "Member B"], "types": ["Photo", "CD", "Merch"] }
```

---

## Reports (`api.php`)

### `report_monthly` — GET

Monthly aggregation of spending and quantity.

**Response:**
```json
{
  "data": [
    { "month": "2024-01", "items": 5, "total_qty": 8, "total_price": 2500.0 }
  ]
}
```

---

### `report_daily` — GET

Daily breakdown for a specific month, including type and idol breakdowns.

**Query Parameters:** `month` (YYYY-MM, required)

**Response:**
```json
{
  "data": [ { "day": "2024-01-15", "items": 2, "total_qty": 3, "total_price": 900.0 } ],
  "months": ["2024-02", "2024-01"],
  "by_type": [ { "type": "Photo", "items": 1, "total_qty": 2, "total_price": 600.0 } ],
  "by_idol": [ { "idol": "Member A", "items": 2, "total_qty": 3, "total_price": 900.0 } ]
}
```

---

### `report_dashboard` — GET

Consolidated payload for the Dashboard landing page (`index.php`). Returns KPIs, monthly trend,
top members/groups, and type/company breakdowns in a single round-trip. The group/company aggregates
use the same membership-aware joins as `report_by_group` / `report_by_company`, so numbers match the
Report page.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `date_from` | YYYY-MM-DD | — | Lower bound on `order_date` (empty = no bound) |
| `date_to` | YYYY-MM-DD | — | Upper bound on `order_date` (empty = no bound) |

`top_members` / `top_groups` are capped at 5 rows. `years` is the distinct list of years that have
data (unfiltered) — used to populate the period selector.

**Response:**
```json
{
  "kpis": {
    "total_items": 120, "total_qty": 210, "total_spent": 65000.0,
    "active_months": 14, "avg_per_month": 4642.8,
    "latest_month": "2026-05", "latest_month_spent": 5200.0,
    "prev_month_spent": 4800.0, "mom_change_pct": 8.3,
    "top_member": "Yuna [ITZY]", "top_group": "ITZY", "top_type": "Photo"
  },
  "monthly": [ { "month": "2026-05", "items": 5, "total_qty": 8, "total_price": 5200.0 } ],
  "top_members": [ { "idol_id": 42, "idol": "Yuna", "display": "Yuna [ITZY]", "items": 10, "total_qty": 15, "total_price": 4500.0 } ],
  "top_groups": [ { "group_id": 10, "name": "ITZY", "category": "group", "parent": "JYP", "items": 30, "total_qty": 50, "total_price": 15000.0 } ],
  "by_type": [ { "type": "Photo", "items": 20, "total_qty": 35, "total_price": 10500.0 } ],
  "by_company": [ { "name": "JYP", "items": 60, "total_qty": 100, "total_price": 30000.0 } ],
  "years": ["2026", "2025", "2024"]
}
```

`kpis.mom_change_pct` is `null` when there is no prior month or the prior month had zero spending.

---

### `report_idol` — GET

Spending ranking by individual member (only `member` category entities, or all idols as fallback).

**Response:**
```json
{
  "data": [ { "idol": "Member A", "items": 10, "total_qty": 15, "total_price": 4500.0 } ]
}
```

---

### `report_idol_detail` — GET

Type breakdown and monthly breakdown for a single idol. Accepts either `idol_id` (preferred — disambiguates between same-name entities) or `idol` (legacy text match).

**Query Parameters:** `idol_id` (int) **or** `idol` (string) — at least one required

**Response:**
```json
{
  "by_type": [ { "type": "Photo", "items": 3, "total_qty": 5, "total_price": 1500.0 } ],
  "by_month": [ { "month": "2024-01", "items": 2, "total_qty": 3, "total_price": 900.0 } ]
}
```

---

### `report_type` — GET

Spending ranking by item type.

**Response:**
```json
{
  "data": [ { "type": "Photo", "items": 20, "total_qty": 35, "total_price": 10500.0 } ]
}
```

---

### `report_type_detail` — GET

Member breakdown and monthly breakdown for a single type.

**Query Parameters:** `type` (string, required)

**Response:**
```json
{
  "members": [
    { "member": "Member A", "group": "Group X", "company": "Company Z", "items_count": 5, "total_qty": 8, "total_price": 2400.0 }
  ],
  "by_month": [ { "month": "2024-01", "items": 3, "total_qty": 5, "total_price": 1500.0 } ]
}
```

---

### `report_by_group` — GET

Spending aggregated by group/unit, rolling up all member spending.

**Response:**
```json
{
  "data": [
    { "name": "Group X", "category": "group", "parent": "Company Z", "items": 30, "total_qty": 50, "total_price": 15000.0, "members": ["Member A", "Member B"] }
  ]
}
```

---

### `report_by_company` — GET

Spending aggregated by company, with group breakdown.

**Response:**
```json
{
  "data": [
    {
      "name": "Company Z",
      "items": 60,
      "total_qty": 100,
      "total_price": 30000.0,
      "groups": [ { "name": "Group X", "category": "group", "items": 30, "total_qty": 50, "total_price": 15000.0 } ]
    }
  ]
}
```

---

## Idol Entities (`api.php`)

### `idol_entities_tree` — GET

Get all idol entities with hierarchy info and spending stats. Also returns parent list for dropdowns.

**Response:**
```json
{
  "entities": [
    { "id": 1, "name": "Company Z", "category": "company", "parent_id": null, "sort_order": 0, "items_count": 60, "total_qty": 100, "total_price": 30000.0 }
  ],
  "parents": [
    { "id": 1, "name": "Company Z", "category": "company" }
  ]
}
```

---

### `idol_entity_save` — POST

Create or update an idol entity. For `member` entities, **a default primary membership** is auto-created from `parent_id` (one row in `idol_memberships`), and `items.idol_id` is auto-backfilled for previously-unmapped items whose name uniquely matches.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit or `0` to create; provide to update |
| `name` | string | Entity name (required; duplicates allowed across the table) |
| `category` | string | `company`, `group`, `unit`, or `member` |
| `parent_id` | int | Parent entity ID (empty = no parent) |
| `sort_order` | int | Display order |
| `display_hint` | string | Optional disambiguation label (e.g. `"ITZY"`) |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 5, "backfilled_items": 3 }
```

---

### `idol_entity_delete` — POST

Delete an idol entity. Children are orphaned (parent_id set to NULL).

**POST Body:** `id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

## Type Categories (`api.php`)

### `type_list` — GET

List all type categories with usage stats and unmapped type names.

**Response:**
```json
{
  "types": [
    { "id": 1, "name": "Photo", "description": "Photo cards", "sort_order": 1, "items_count": 20, "total_qty": 35, "total_price": 10500.0 }
  ],
  "unmapped": ["NewType"]
}
```

---

### `type_members_report` — GET

All types with member/group/company breakdown.

**Response:**
```json
{
  "by_type": {
    "Photo": [
      { "member": "Member A", "group": "Group X", "company": "Company Z", "items_count": 5, "total_qty": 8, "total_price": 2400.0 }
    ]
  }
}
```

---

### `type_save` — POST

Create or update a type category.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit or `0` to create; provide to update |
| `name` | string | Type name (required) |
| `description` | string | Description |
| `sort_order` | int | Display order |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 3 }
```

---

### `type_delete` — POST

Delete a type category.

**POST Body:** `id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

## Budgets (`api.php`)

Recurring monthly spending limits per scope. See the `budgets` table in [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md).

### `budget_list` — GET

Two modes:
- **`?mode=defaults`** — recurring default definitions only (`period IS NULL`), for the Manage view. No spending fields.
- **`?month=YYYY-MM`** (default) — **effective** budgets for the month: per scope, a month override wins over the recurring default. Enriched with spending and colour status. **`budget_progress`** returns this same payload (dashboard card / report tab).

**Query:** `mode` (`defaults` | omitted) · `month` (optional `YYYY-MM`, defaults to current month)

**Response (effective / progress):**
```json
{
  "month": "2026-06",
  "budgets": [
    {
      "id": 7, "scope_type": "overall", "scope_ref_id": null, "scope_ref_name": "",
      "period": "2026-06", "label": "Overall", "display_hint": "",
      "amount": 12000, "warn_pct": 50, "danger_pct": 90, "note": "",
      "spent": 4200, "remaining": 7800, "pct": 35, "status": "ok",
      "is_override": true, "has_default": true, "default_id": 6, "override_id": 7
    }
  ]
}
```
`status` is `ok` | `near` | `over`. `is_override` = a month override applies; `has_default` = a recurring default also exists; `default_id` / `override_id` identify the underlying rows (used by "edit this month" / "reset to default").

### `budget_save` — POST

Create or update a budget. Duplicate (same scope + same period) returns HTTP `409`.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit or `0` to create; provide to update |
| `scope_type` | string | `overall`, `type`, `group`, `company`, `member` (required) |
| `scope_ref_name` | string | Required when `scope_type='type'` (the type name) |
| `scope_ref_id` | int | Required when `scope_type` is group/company/member (`idol_entities.id`) |
| `amount` | number | Monthly limit (฿), `≥ 0` |
| `warn_pct` | int | Yellow threshold % (default 80) |
| `danger_pct` | int | Red threshold % (default 100); requires `1 ≤ warn_pct ≤ danger_pct ≤ 1000` |
| `period` | string | Omit/empty = recurring default; `'YYYY-MM'` = override for that month |
| `note` | string | Optional |
| `csrf_token` | string | CSRF token |

**Response:** `{ "success": true, "id": 3 }`

### `budget_delete` — POST

Delete a budget. Deleting a month override reverts that month to the recurring default ("reset").

**POST Body:** `id` (int, required), `csrf_token`

**Response:** `{ "success": true }`

---

## Backups — Admin Only (`api.php`)

### `backup_list` — GET

List all backup files with metadata.

**Response:**
```json
{
  "backups": [
    { "filename": "backup_20240115_120000.sqlite", "size": 204800, "created": "2024-01-15 12:00:00" }
  ]
}
```

---

### `backup_create` — POST

Create a new backup snapshot.

**POST Body:** `label` (string, optional — alphanumeric/underscore/hyphen only), `csrf_token`

**Response:**
```json
{ "success": true, "filename": "backup_20240115_120000_mylabel.sqlite", "size": 204800 }
```

---

### `backup_restore` — POST

Restore the database from a backup. An automatic pre-restore backup is created first.

**POST Body:** `filename` (string, required), `csrf_token`

**Response:**
```json
{ "success": true, "message": "Restored from backup_20240115_120000.sqlite. Auto-backup created." }
```

---

### `backup_delete` — POST

Delete a backup file.

**POST Body:** `filename` (string, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

### `backup_download` — GET

Download a backup file as a binary stream.

**Query Parameters:** `filename` (string, required — must end with `.sqlite`)

**Response:** Binary file download (`Content-Type: application/octet-stream`).

---

## Users (`api_users.php`)

### `list` — GET

List all users. Available to all authenticated users; only admin should expose this UI.

**Response:**
```json
{
  "users": [
    { "id": 1, "username": "admin", "display_name": "Administrator", "role": "admin", "last_login": "2024-01-15 10:00:00", "created_at": "2024-01-01 00:00:00" }
  ]
}
```

---

### `save` — POST — Admin Only

Create or update a user.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit or `0` to create; provide to update |
| `username` | string | Login username (required; ignored on update) |
| `display_name` | string | Display name (required) |
| `password` | string | Min 12 characters. Required for create; leave empty to keep existing on update |
| `role` | string | `admin` or `user` |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 2 }
```

---

### `delete` — POST — Admin Only

Delete a user. Cannot delete yourself.

**POST Body:** `id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

### `change_password` — POST

Change your own password. Available to all authenticated users.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `current_password` | string | Current password (required) |
| `new_password` | string | New password, min 12 characters (required) |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true }
```

---

## v5 Endpoints — Idol Resolution, Memberships, Conflict Handling

### `idol_search` — GET

Searchable lookup against `idol_entities`. Used by the item form to pick the right entity when names collide.

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `q` | string | — | Substring match against `name` |
| `category` | string | `member` | `company`, `group`, `unit`, `member`, or `any` |

**Response:**
```json
{
  "data": [
    { "id": 42, "name": "Yuna", "category": "member", "parent_id": 10, "display_hint": "ITZY", "display": "Yuna [ITZY]" }
  ]
}
```

---

### `idol_resolve_name` — GET

Resolve a free-text idol name to an entity (or report ambiguity).

**Query Parameters:** `name` (string, required)

**Response:**
```json
{
  "id": null,
  "ambiguous": true,
  "candidates": [
    { "id": 42, "name": "Yuna", "display_hint": "ITZY",  "display": "Yuna [ITZY]" },
    { "id": 87, "name": "Yuna", "display_hint": "AKB48", "display": "Yuna [AKB48]" }
  ]
}
```

---

### `item_remap` — POST

Reassign a single item's `idol_id` (used by the Conflict Resolution UI).

**POST Body:** `item_id` (int, required), `idol_id` (int, optional — set to empty to clear), `csrf_token`

**Response:** `{ "success": true }`

---

### `item_bulk_remap` — POST

Reassign all unmapped items with a matching `idol` text within an optional date range.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `idol_name` | string | Text to match against `items.idol` |
| `idol_id` | int | Target entity ID |
| `date_from` | YYYY-MM-DD | Optional lower bound on `order_date` |
| `date_to` | YYYY-MM-DD | Optional upper bound on `order_date` |
| `csrf_token` | string | CSRF token |

**Response:** `{ "success": true, "updated": 23 }`

---

### `ambiguous_list` — GET

List distinct `items.idol` values that are currently unmapped *and* have more than one matching member entity. Used by the Conflict Resolution UI.

**Response:**
```json
{
  "data": [
    {
      "name": "Yuna",
      "items_count": 45,
      "candidates": [
        { "id": 42, "name": "Yuna", "display_hint": "ITZY",  "display": "Yuna [ITZY]" },
        { "id": 87, "name": "Yuna", "display_hint": "AKB48", "display": "Yuna [AKB48]" }
      ]
    }
  ]
}
```

---

### `membership_list` — GET

List all memberships for a member, oldest first.

**Query Parameters:** `member_id` (int, required)

**Response:**
```json
{
  "data": [
    {
      "id": 12, "member_id": 42, "group_id": 10, "is_primary": 1,
      "start_date": null, "end_date": "2025-07-31",
      "note": "", "group_name": "ITZY", "group_category": "group", "group_display": "ITZY"
    }
  ]
}
```

---

### `membership_save` — POST

Create or update a membership row.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit to create; provide to update |
| `member_id` | int | Member entity (required) |
| `group_id` | int | Group/unit/company entity (required) |
| `start_date` | YYYY-MM-DD | Optional — open lower bound if omitted |
| `end_date` | YYYY-MM-DD | Optional — open upper bound if omitted |
| `is_primary` | 0/1 | `1` = main group (default), `0` = sub-unit |
| `note` | string | Optional free-text note |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 12, "warnings": ["Primary membership overlaps with an existing primary period for this member."] }
```

`warnings` is non-empty when an overlapping primary period is detected (loose policy — save still succeeds).

---

### `membership_delete` — POST

Delete a membership row.

**POST Body:** `id` (int, required), `csrf_token`

**Response:** `{ "success": true }`

---

### `membership_move` — POST

Shortcut: close the current open primary membership on `move_date - 1` and create a new one starting on `move_date`.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `member_id` | int | Member to move (required) |
| `new_group_id` | int | New group/unit (required) |
| `move_date` | YYYY-MM-DD | Date the new membership starts (required) |
| `csrf_token` | string | CSRF token |

**Response:** `{ "success": true, "new_membership_id": 13 }`

---

### `report_group_detail` — GET

Drill-down for a single group: primary members, sub-unit memberships (non-primary), and monthly breakdown.

**Query Parameters:** `group_id` (int, preferred) **or** `group` (string)

**Response:**
```json
{
  "members":   [ { "idol_id": 42, "idol": "Yuna", "display": "Yuna [ITZY]", "items": 10, "total_qty": 15, "total_price": 4500.0 } ],
  "sub_units": [ { "membership_id": 25, "idol_id": 42, "idol": "Yuna", "start_date": "2024-09-01", "end_date": null, "is_primary": 0 } ],
  "by_month":  [ { "month": "2024-09", "items": 3, "total_qty": 5, "total_price": 1500.0 } ]
}
```
