# API Endpoints

## Overview

The API is split into two files:
- **`api.php`** — Items, Reports, Idol Entities, Type Categories, Backups
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
| `per_page` | int | 20 | Items per page (max 100) |
| `idol[]` | string[] | — | Filter by one or more idol names |
| `type[]` | string[] | — | Filter by one or more type names |
| `search` | string | — | Full-text search on `title` |
| `date_from` | YYYY-MM-DD | — | Order date range start |
| `date_to` | YYYY-MM-DD | — | Order date range end |
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

Create a new item.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `order_date` | YYYY-MM-DD | Purchase date |
| `event_date` | YYYY-MM-DD | Event date (can be empty) |
| `title` | string | Item name |
| `idol` | string | Idol / member name |
| `type` | string | Item type |
| `price_per_qty` | float | Price per unit |
| `qty` | int | Quantity |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 43 }
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

Type breakdown and monthly breakdown for a single idol.

**Query Parameters:** `idol` (string, required)

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

Create or update an idol entity.

**POST Body:**

| Field | Type | Description |
|-------|------|-------------|
| `id` | int | Omit or `0` to create; provide to update |
| `name` | string | Entity name (required) |
| `category` | string | `company`, `group`, `unit`, or `member` |
| `parent_id` | int | Parent entity ID (empty = no parent) |
| `sort_order` | int | Display order |
| `csrf_token` | string | CSRF token |

**Response:**
```json
{ "success": true, "id": 5 }
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
