# API Endpoints

## Overview

The API is split into two files:
- **`api.php`** — Items, Reports, Idol Entities, Type Categories, Events, Budgets, Backups
- **`api_users.php`** — User management

All responses are JSON (`Content-Type: application/json`).
All endpoints require an active login session (returns `401` if unauthenticated).

### `include_excluded` (v12) — accepted by every read endpoint

Items whose type category is flagged `exclude_from_reports = 1` are left out of all
aggregates by default. Pass `include_excluded=1` on any read endpoint (including
`export.php`) to count them.

Three carve-outs ignore the flag entirely, all from the same rule — *if the caller points
at a type directly, they get the full amount*:

1. `budget_progress` / `budget_list` rows with `scope_type='type'`
2. `report_type_detail`
3. `list` and `export.php` when `type[]` is supplied

`type_list` and `filters` are also unfiltered (Manage Types must report the excluded
type's real totals; the filter dropdowns must still offer excluded types). `event_list` is
unfiltered too — see the note under **Events**.

### `date_from` / `date_to` (v1.12.0) — report date range

Every `report_*` endpoint, plus `excluded_summary`, accepts optional `date_from` /
`date_to` (`YYYY-MM-DD`, inclusive; invalid values are ignored). By default they bound
`order_date`. Two exceptions:

- `report_event` / `report_event_summary` keep events whose date span **overlaps** the range
- `report_inactive` uses only `date_to`, as the "as of" date

Management endpoints (`idol_entities_tree`, `type_list`, `event_list`) ignore the range.

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
  "summary": { "total_price": 15000.0, "total_qty": 38 },
  "excluded": { "items": 3, "total_qty": 3, "total_price": 8400.0 }
}
```

`excluded` is the slice held back by `exclude_from_reports`, measured under the *same*
filters as `data`, and is reported in both modes so a shorter table is never mistaken for
missing data. Supplying `type[]` disables the exclusion for this endpoint (carve-out 3).

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
{ "idols": ["Member A", "Member B"], "types": ["Photo", "CD", "Merch"], "excluded_types": ["Travel"] }
```

Deliberately unfiltered: the dropdowns must still offer excluded types, or the `type[]`
carve-out on `list` is unreachable and those items become invisible. `excluded_types`
(v12) lets the client mark them.

---

## Reports (`api.php`)

### `excluded_summary` — GET

Lightweight summary of the `exclude_from_reports` slice. Drives the banners on
`report.php` and `items.php` without loading a whole report first.

**Response:**
```json
{
  "enabled": true,
  "types": ["Travel"],
  "by_type": [ { "type": "Travel", "items": 3, "total_qty": 3, "total_price": 8400.0 } ],
  "items": 3, "total_qty": 3, "total_price": 8400.0
}
```

`enabled` is `false` when no type is flagged — clients hide the banner entirely in that
case. Ignores `include_excluded`: this is always the excluded-only slice, so the same
figure can be shown in both modes.

---

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
| `include_excluded` | `1` | — | Count types flagged `exclude_from_reports` |

Also returns `excluded: { items, total_qty, total_price }` — the slice left out of the
KPIs, over the same date window. **Not a plain subtraction from `total_spent`:** a month
whose only spending was excluded also drops out of `monthly`, which changes
`active_months` — the denominator of `avg_per_month`, not just the numerator.

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
  "data": [ { "type": "Photo", "items": 20, "total_qty": 35, "total_price": 10500.0 } ],
  "excluded": [ { "type": "Travel", "items": 3, "total_qty": 3, "total_price": 8400.0 } ]
}
```

`excluded` is the per-type breakdown of the flagged types. This is the one tab whose job
is listing types, so they are shipped separately rather than simply dropped — the client
renders them as a muted section below the table.

---

### `report_type_detail` — GET

Member breakdown and monthly breakdown for one type, or several types viewed combined
(e.g. `cheki` + `pin cheki`).

**Query Parameters:** `type[]` (string, repeatable) or `type` (string, legacy single) — at least one required; optional `date_from` / `date_to` (YYYY-MM-DD)

**Response:**
```json
{
  "types": ["cheki", "pin cheki"],
  "members": [
    { "member": "Member A", "group": "Group X", "company": "Company Z", "items_count": 5, "total_qty": 8, "total_price": 2400.0 }
  ],
  "by_month": [ { "month": "2024-01", "items": 3, "total_qty": 5, "total_price": 1500.0 } ],
  "by_month_type": [ { "month": "2024-01", "type": "cheki", "items": 2, "total_qty": 3, "total_price": 900.0 } ],
  "by_type": [ { "type": "cheki", "items": 4, "total_qty": 6, "total_price": 1800.0 } ]
}
```

`members` aggregates across all selected types (one row per member). `by_month` and
`by_type` are rolled up from `by_month_type`, so they always agree.

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

### `report_by_unit` — GET

Spending rolled up to `unit`-category entities through `idol_memberships` (primary **and**
non-primary), matched by `order_date` against each membership period. This catches the
sub-unit / project memberships that `report_by_group` leaves out.

**Response:**
```json
{
  "data": [
    { "unit_id": 15, "name": "Unit Y", "parent": "Company Z", "items": 12, "total_qty": 20, "total_price": 6000.0, "members": 3 }
  ]
}
```

---

### `report_event` — GET

Spending per named event, plus the unlinked items grouped by `event_date`.

**Date range:** named events are kept when their `event_date…COALESCE(end_date, event_date)`
span **overlaps** `date_from`/`date_to` (`reportEventOverlapClause()`). Every linked item
counts, including items ordered before the range. Unlinked rows and lead-time filter on
`event_date`. `no_event` filters on `order_date`, because those items have no event date.

**Response:**
```json
{
  "named": [
    { "event_id": 4, "event_name": "Tour BKK", "event_date": "2026-03-01", "end_date": "2026-03-02",
      "is_named": true, "items": 6, "total_qty": 9, "total_price": 7200.0, "idols": 3, "types": 2 }
  ],
  "unlinked": [
    { "event_id": null, "event_name": null, "event_date": "2025-12-10", "end_date": null,
      "is_named": false, "items": 2, "total_qty": 2, "total_price": 900.0, "idols": 1, "types": 1 }
  ],
  "lead_time": { "n": 40, "avg_days": 21.5, "min_days": 0, "max_days": 120 },
  "no_event": 85
}
```

`lead_time` is `event_date − order_date` in days, over items that have both dates.

---

### `report_event_summary` — GET

One row per named event, with its duration, status and per-day spend. It uses the same
event-overlap date rule as `report_event`.

**Response:**
```json
{
  "events": [
    { "id": 4, "name": "Tour BKK", "start_date": "2026-03-01", "end_date": "2026-03-02", "days": 2,
      "status": "past", "items": 6, "total_qty": 9, "total_price": 7200.0, "avg_per_day": 3600.0 }
  ],
  "summary": { "total": 12, "multi_day": 3, "total_days": 16, "upcoming": 2, "ongoing": 0 }
}
```

`status` is `upcoming` | `ongoing` | `past`, compared with today's date. `days` is at least `1`.

---

### `report_top_items` — GET

Extremes: the 20 most expensive line items, the 20 most frequently bought titles, and
unit-price statistics per type. Filters on `order_date`.

**Response:**
```json
{
  "expensive": [
    { "id": 101, "title": "Lightstick", "idol": "Member A", "type": "Goods", "order_date": "2026-01-05",
      "event_date": null, "price_per_qty": 1800.0, "qty": 2, "line_total": 3600.0 }
  ],
  "frequent": [ { "title": "Cheki", "items": 40, "total_qty": 55, "total_price": 16500.0 } ],
  "avg_price": [
    { "type": "Cheki", "items": 40, "total_qty": 55, "avg_price": 300.0, "min_price": 250.0, "max_price": 500.0, "total_price": 16500.0 }
  ]
}
```

---

### `report_seasonality` — GET

Spending by day of week and by month of year, summed across all years. Filters on `order_date`.

**Response:**
```json
{
  "weekday": [ { "dow": 0, "items": 10, "total_qty": 14, "total_price": 4200.0 } ],
  "month_of_year": [ { "moy": 1, "items": 8, "total_qty": 12, "total_price": 3600.0 } ]
}
```

`dow`: `0` = Sunday … `6` = Saturday. `moy`: `1`–`12`.

---

### `report_inactive` — GET

Every idol with at least one item, with the date of their last purchase and the number of
days since. The client applies the inactivity threshold (30 / 90 / 180 / 365 days).

**Date range:** only `date_to` is used. It is the "as of" date for `days_since`, and later
orders are ignored. `date_from` is ignored on purpose, because it would hide the members
who stopped buying before the range began.

**Response:**
```json
{
  "data": [
    { "idol_id": 42, "idol": "Yuna", "display_hint": "ITZY", "display": "Yuna [ITZY]",
      "items": 10, "total_qty": 15, "total_price": 4500.0, "last_order": "2025-08-01", "days_since": 109 }
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
    { "id": 1, "name": "Photo", "description": "Photo cards", "sort_order": 1, "is_ticket": 0, "exclude_from_reports": 0, "items_count": 20, "total_qty": 35, "total_price": 10500.0 }
  ],
  "unmapped": ["NewType"]
}
```

`is_ticket` (v1.9.15): `1` marks this type as representing an event admission ticket — used by the Events page's missing-ticket detection.

`exclude_from_reports` (v1.10.0): `1` leaves this type's items out of the normal totals.
**These stats are deliberately unfiltered** — Manage Types must report the excluded type's
real totals, since that is where the user goes to see what the exclusion is costing them.

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
| `is_ticket` | bool | (v1.9.15) `1` to flag this type as a ticket type |
| `exclude_from_reports` | bool | (v1.10.0) `1` to leave this type's items out of the normal totals |
| `csrf_token` | string | CSRF token |

On update, the response also carries `orphaned_items` and `old_name`: `items.type` is free
text with no FK, so a rename leaves existing items behind under the old name where they
lose the category's flags. For `exclude_from_reports` that moves money — the orphans
re-enter every total at once — so the client should offer `type_rename_items`.

**Response:**
```json
{ "success": true, "id": 3 }
```

---

### `type_rename_items` — POST

Re-point items left behind by a type-category rename (see `type_save`).

**POST Body:** `from` (string, required), `to` (string, required), `csrf_token`

**Response:**
```json
{ "success": true, "updated": 12 }
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

## Item Presets (`api.php`, v13)

Form fillers for the Add Item form. Items are never linked to a preset, so these endpoints never touch `items`.

### `preset_list` — GET

All presets, ordered by `sort_order, name`.

**Response:**
```json
{ "presets": [
  { "id": 2, "name": "Deco Cheki", "type": "Pin Cheki", "price_per_qty": 500.0, "title": "Deco", "sort_order": 1 }
] }
```

---

### `preset_save` — POST

Create a preset (no `id`) or update one (`id` > 0).

**POST Body:** `id` (int, optional), `name` (required), `type` (required), `price_per_qty` (number ≥ 0, required), `title` (optional, `''` = none), `sort_order` (int), `csrf_token`

**Response:**
```json
{ "success": true, "id": 2, "warning": "unknown_type" }
```
`warning: "unknown_type"` is present when `type` does not match any `type_categories.name`. The preset is still saved, because `items.type` is free text.
Errors: 400 if name or type is missing or the price is invalid, 404 if `id` does not exist.

---

### `preset_delete` — POST

**POST Body:** `id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

## Events (`api.php`)

### `event_list` — GET

List all events with linked-item stats and ticket-detection fields.

> **Deliberately ignores `exclude_from_reports`.** `events.php` is operational rather than
> analytical: ticket detection answers "did I buy admission for this event?", and a type
> can be both a ticket type and excluded (a ticket bought for a friend). Filtering here
> would turn that into a false "missing ticket" warning and would quietly make excluded
> items unassignable via `unassigned_same_date`. The **Event** and **Event Summary** tabs
> in `report.php` (`report_event`, `report_event_summary`) *are* filtered — events whose
> only items are excluded still appear there, with totals of 0.

**Response:**
```json
{
  "events": [
    {
      "id": 1, "name": "TWICE World Tour Bangkok", "event_date": "2025-09-01", "end_date": null,
      "description": "", "created_at": "2025-08-01 10:00:00", "is_free_entry": 0, "is_not_attended": 0,
      "items_count": 3, "total_price": 5500.0, "unassigned_same_date": 0, "ticket_items_count": 1
    }
  ],
  "ticket_types_count": 1
}
```

`is_free_entry`, `ticket_items_count`, and the top-level `ticket_types_count` were added in v1.9.15;
`is_not_attended` was added in v1.9.16. An event's ticket status (used by the Events page badge) is
derived client-side: `is_not_attended=1` → **Did not attend**; else `is_free_entry=1` → **Free
entry**; else `ticket_items_count > 0` → **Ticket recorded**; else → **No ticket**. `ticket_types_count`
is the number of `type_categories` rows flagged `is_ticket=1` — when `0`, no type has been configured yet
and the UI shows a setup hint instead of status badges.

---

### `event_save` — POST

Create or update a named event.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit or `0` to create; provide to update |
| `name` | string | Event name (required) |
| `event_date` | string | Start date, `YYYY-MM-DD` (required) |
| `end_date` | string | End date, `YYYY-MM-DD`; blank or equal to `event_date` means a single-day event |
| `description` | string | Optional description |
| `is_free_entry` | bool | (v1.9.15) `1` marks the event as free entry (excluded from missing-ticket detection) |
| `is_not_attended` | bool | (v1.9.16) `1` marks the event as not attended (excluded from missing-ticket detection); clears `is_free_entry` server-side if both are sent |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 5 }
```

---

### `event_delete` — POST

Delete an event. Linked items are not deleted — their `event_id` is set to `NULL`.

**POST Body:** `id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true }
```

---

### `event_bulk_assign` — POST

Link or unlink a list of items to/from an event.

**POST Body:** `ids` (int[] or comma-separated string, required), `event_id` (int; omit/`0` to unassign), `csrf_token`

**Response:**
```json
{ "success": true, "updated": 4 }
```

---

### `event_auto_assign` — POST

Link all currently-unassigned items whose `event_date` falls within the event's date range.

**POST Body:** `event_id` (int, required), `csrf_token`

**Response:**
```json
{ "success": true, "updated": 2 }
```

---

## Budgets (`api.php`)

Recurring monthly spending limits per scope. See the `budgets` table in [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md).

### `budget_list` — GET

Two modes:
- **`?mode=defaults`** — recurring default definitions only (`period IS NULL`), for the Manage view. No spending fields.
- **`?month=YYYY-MM`** (default) — **effective** budgets for the month: per scope, a month override wins over the recurring default. Enriched with spending and colour status. **`budget_progress`** returns this same payload (dashboard card).

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

### `budget_progress` — GET

Same payload as `budget_list?month=YYYY-MM` (effective budgets with spending + status).
Used by the dashboard card.

### `budget_matrix` — GET

Scopes × Months grid used by the **Monthly** tab on `budget.php`. It returns budget
**amounts only**, with no spending figures.

**Query:** `from`, `to` (`YYYY-MM`; swapped if reversed; capped at 240 months)

**Response:**
```json
{
  "from": "2026-01", "to": "2026-12",
  "months": ["2026-01", "2026-02", "…"],
  "scopes": [
    {
      "scope_type": "member", "scope_ref_id": 42, "scope_ref_name": "Yuna",
      "label": "Yuna", "display_hint": "ITZY",
      "default": { "id": 6, "amount": 3000, "warn_pct": 80, "danger_pct": 100, "note": "" },
      "cells": { "2026-03": { "override_id": 9, "amount": 5000, "warn_pct": 80, "danger_pct": 100, "note": "concert" } }
    }
  ]
}
```

`default` is `null` when the scope has only overrides. `cells` holds only the overridden
months. Every other month uses `default`.

### `budget_analytics` — GET

Spending against budget for one scope over several months. Powers the **Insights** tab.

**Query:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `scope_type` | string | `overall` | `overall` / `type` / `group` / `company` / `member` |
| `scope_ref_id` | int | — | Entity id (group/company/member) |
| `scope_ref_name` | string | — | Type name (`scope_type='type'`) |
| `months` | int | `12` | Window ending at the current month (1–36) |

**Response:**
```json
{
  "scope": { "scope_type": "overall", "scope_ref_id": null, "scope_ref_name": "", "label": "", "display_hint": "" },
  "months": [
    { "month": "2026-11", "budget": 12000, "spent": 9800, "pct": 81.7, "status": "near", "has_budget": true, "over": false }
  ],
  "scopes": [ { "scope_type": "overall", "scope_ref_id": null, "scope_ref_name": "", "label": "", "display_hint": "" } ],
  "summary": {
    "months_tracked": 12, "months_with_budget": 12, "total_spent": 98000, "avg_spent": 8166.7,
    "avg_budget": 12000, "avg_pct": 68.1, "over_count": 1, "near_count": 3,
    "max_spent": { "month": "2026-03", "amount": 15200 }
  },
  "recommendations": [ { "code": "over_frequent", "severity": "danger", "params": { "m": 3, "n": 12 } } ]
}
```

`status` is `ok` | `near` | `over` | `none` (no budget that month). `scopes` lists every
scope that has an active budget (for the selector). Recommendation `code`s include
`no_budget`, `over_frequent`, `over_recent` and `projection`, plus trend and on-track codes.
The client translates them.

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

### `item_bulk_set_type` — POST

Set the type on a set of selected items in one go — backs the **Change Type** button in
the Items page's bulk action bar.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `type` | string | New type name (required, trimmed) |
| `ids[]` | int[] | Item IDs to update (also accepts a comma-separated string) |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "updated": 3, "type": "Travel", "excluded": true }
```

`items.type` is free text with no FK, so — like the item form — a name that does not exist
in `type_categories` is accepted rather than rejected; it simply shows up in the "Unmapped
Type Names" panel on `types.php`.

`excluded` is `true` when the destination type is flagged `exclude_from_reports`. The
client uses it to warn that the rows it just changed are about to leave the list, so a
successful bulk move is never mistaken for a failed one.

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
