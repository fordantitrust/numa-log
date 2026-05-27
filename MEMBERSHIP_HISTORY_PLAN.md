# Membership History + ID-Based Reference — Implementation Plan

แผน refactor การอ้างอิงไอดอลในระบบ เพื่อแก้ 2 ปัญหาที่มีรากเดียวกัน:

1. **Membership History** — ไอดอลย้ายวง ต้อง trace ของเก่า/ใหม่แยกกันโดยใช้ชื่อเดิม
2. **Duplicate Names** — ชื่อไอดอลซ้ำกันได้ (เช่น "Yuna" ใน ITZY กับ AKB48) ต้องแยกแยะได้

ทั้งสองปัญหาเกิดจากรากเดียวกัน: `idol_entities.name` ถูกใช้เป็น **natural key** ในการ join กับ `items.idol` (TEXT match) ซึ่งเปราะทั้งเรื่องชื่อซ้ำและการย้ายวง

**Target version:** v1.5.0
**Status:** Draft

---

## 1. Goals & Non-goals

### Goals
- ไอดอลย้ายวงได้ → items เก่าเข้ากับวงเก่า, items ใหม่เข้ากับวงใหม่ (ตาม `order_date`)
- รองรับไอดอลคนละคนชื่อซ้ำกัน (different entity, same `name`)
- รองรับไอดอลอยู่หลายวงพร้อมกัน (main + sub-unit + project)
- รองรับ graduate / disband
- Items reference idol ผ่าน **surrogate ID (FK)** แทน string match
- Migration จาก v1.4.x ทำงานอัตโนมัติ + มี conflict resolution UI สำหรับ ambiguous mapping

### Non-goals
- ไม่ rename entity (วงเปลี่ยนชื่อ → สร้าง entity ใหม่ + membership ใหม่)
- **ไม่ drop `items.idol` (text) เลย** — เก็บเป็น immutable snapshot ของชื่อ ณ วันที่ซื้อ (ป้องกัน "amnesia" เวลา entity ถูก rename/delete)
- ไม่ลบ `idol_entities.parent_id` ใน phase แรก (deprecate ภายหลัง — กลายเป็น UI cache สำหรับ tree view)
- ไม่เพิ่ม `items.group_id_override` (per-item group override) ใน v1.5 — รอ user feedback ว่าจำเป็นมั้ย ค่อยเพิ่มแบบ additive ใน v1.6

---

## 2. Schema Changes

### 2.1 ตารางใหม่: `idol_memberships`

```sql
CREATE TABLE IF NOT EXISTS idol_memberships (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id   INTEGER NOT NULL REFERENCES idol_entities(id) ON DELETE CASCADE,
    group_id    INTEGER NOT NULL REFERENCES idol_entities(id) ON DELETE CASCADE,
    start_date  TEXT,         -- YYYY-MM-DD, NULL = ตั้งแต่แรก
    end_date    TEXT,         -- YYYY-MM-DD, NULL = ปัจจุบัน
    is_primary  INTEGER NOT NULL DEFAULT 1,
    note        TEXT DEFAULT '',
    created_at  TEXT DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_mb_member       ON idol_memberships(member_id);
CREATE INDEX idx_mb_group        ON idol_memberships(group_id);
CREATE INDEX idx_mb_member_dates ON idol_memberships(member_id, start_date, end_date);
```

### 2.2 ปรับ `idol_entities` — รองรับชื่อซ้ำ + เพิ่ม display hint

SQLite ไม่ support `DROP CONSTRAINT` → ต้อง recreate table:

```sql
PRAGMA foreign_keys = OFF;
BEGIN TRANSACTION;

CREATE TABLE idol_entities_new (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT NOT NULL,                       -- ไม่ UNIQUE แล้ว
    category      TEXT NOT NULL DEFAULT 'member'
                  CHECK(category IN ('company','group','unit','member')),
    parent_id     INTEGER NULL REFERENCES idol_entities(id) ON DELETE SET NULL,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    display_hint  TEXT DEFAULT '',                     -- ใหม่ — สำหรับ disambiguate ในชื่อซ้ำ
    created_at    TEXT DEFAULT (datetime('now','localtime'))
);

INSERT INTO idol_entities_new (id, name, category, parent_id, sort_order, display_hint, created_at)
SELECT id, name, category, parent_id, sort_order, '', created_at FROM idol_entities;

DROP TABLE idol_entities;
ALTER TABLE idol_entities_new RENAME TO idol_entities;

CREATE INDEX idx_ie_name       ON idol_entities(name);
CREATE INDEX idx_ie_parent_id  ON idol_entities(parent_id);
CREATE INDEX idx_ie_category   ON idol_entities(category);

COMMIT;
PRAGMA foreign_keys = ON;
```

**`display_hint`** = label สั้นๆ ที่ user ตั้งเอง เช่น `"ITZY"`, `"AKB48"`, `"JYP"` — แสดงคู่กับชื่อใน dropdown/report เพื่อ disambiguate (`Yuna [ITZY]` vs `Yuna [AKB48]`). ไม่ unique, ว่างได้

> ทำไมไม่ใช้ `UNIQUE(name, parent_id)`? เพราะ parent_id จะ deprecated เป็น UI cache, source of truth จะอยู่ที่ `idol_memberships` แทน

### 2.3 ปรับ `items` — เพิ่ม FK

```sql
ALTER TABLE items ADD COLUMN idol_id INTEGER
    REFERENCES idol_entities(id) ON DELETE SET NULL;
CREATE INDEX idx_items_idol_id ON items(idol_id);
```

- `items.idol` (TEXT) **คงไว้ถาวร เป็น immutable snapshot** — ตอน save item ระบบ auto-fill จาก `entity.name` ครั้งเดียว แล้วไม่อัปเดตอีกแม้ entity จะถูก rename ภายหลัง (เพื่อเก็บประวัติชื่อ ณ วันที่ซื้อ)
- `items.idol_id` (FK) เป็น canonical reference ใหม่ที่ใช้ใน aggregate query ทั้งหมด
- ถ้า map ไม่ได้ → `idol_id = NULL` → ขึ้น unmapped/ambiguous panel ให้ admin resolve, item ปรากฏใน report เป็น `"(Unassigned)"`

### 2.4 Schema version tracking

```sql
CREATE TABLE IF NOT EXISTS schema_meta (
    key   TEXT PRIMARY KEY,
    value TEXT
);
-- INSERT OR REPLACE INTO schema_meta VALUES ('version', '5');
```

ใช้ป้องกัน re-migrate และ trace ว่า DB อยู่ schema version ไหน

---

## 3. Migration Strategy

### 3.1 File Layout — แยก migration เป็นไฟล์ต่างหาก

เก็บ migration logic ใน `migrations/` ไม่ผูกกับ `config.php` เพราะ:
- Migration code ใช้ครั้งเดียวต่อ version — ไม่ควรอยู่รวมกับ logic ที่ run ทุก request
- แต่ละ version แยกไฟล์ → diff/review/archive ง่าย
- หลังทุก instance upgrade แล้ว → ลบไฟล์ได้โดยไม่กระทบของอื่น

```
numa-log/
├── config.php                       ← มีแค่ trigger + version helper
├── migrations/
│   ├── README.md                    ← อธิบายระบบ versioning, baseline = 4 = v1.4.x
│   ├── _helpers.php                 ← getSchemaVersion, setSchemaVersion, autoBackupBeforeMigration
│   └── v5_idol_refactor.php         ← migration นี้ทั้งหมดอยู่ที่นี่
└── ...
```

### 3.2 Trigger ใน `config.php`

เพิ่มแค่ ~6 บรรทัดใน `initDB()` (หลังจาก CREATE TABLE IF NOT EXISTS เดิมทำงานเสร็จ):

```php
function initDB(): void {
    $pdo = getDB();

    // ... โค้ดเดิม: CREATE TABLE IF NOT EXISTS items, type_categories, users,
    //              idol_entities, login_attempts + indexes ...

    require_once __DIR__ . '/migrations/_helpers.php';
    $currentVer = getSchemaVersion($pdo);

    if ($currentVer < 5) {
        require_once __DIR__ . '/migrations/v5_idol_refactor.php';
        runMigrationV5($pdo);   // ฟังก์ชันอยู่ในไฟล์ที่เพิ่ง require
    }

    // future migrations:
    // if ($currentVer < 6) { require_once '.../v6_xxx.php'; runMigrationV6($pdo); }
}
```

**ข้อดี:** `config.php` สั้น clean, ไฟล์ v5_*.php จะถูก load **เฉพาะตอน DB ยังไม่ migrate** เท่านั้น (require_once guard) — หลัง upgrade แล้ว zero overhead

### 3.3 `migrations/_helpers.php`

```php
function getSchemaVersion(PDO $pdo): int {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_meta (key TEXT PRIMARY KEY, value TEXT)");
    $v = $pdo->query("SELECT value FROM schema_meta WHERE key='version'")->fetchColumn();
    if ($v === false) {
        // First time: detect baseline จากตารางที่มีอยู่
        $hasItems = (bool)$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='items'")->fetchColumn();
        $baseline = $hasItems ? 4 : 0;   // มี items แล้ว = upgrade จาก v1.4.x; ยังไม่มี = fresh install
        $pdo->prepare("INSERT INTO schema_meta (key, value) VALUES ('version', :v)")
            ->execute([':v' => (string)$baseline]);
        return $baseline;
    }
    return (int)$v;
}

function setSchemaVersion(PDO $pdo, int $v): void {
    $pdo->prepare("INSERT OR REPLACE INTO schema_meta (key, value) VALUES ('version', :v)")
        ->execute([':v' => (string)$v]);
}

function autoBackupBeforeMigration(int $targetVer): string {
    $stamp = date('Ymd_His');
    $backupFile = BACKUP_DIR . "/pre-v{$targetVer}-{$stamp}.sqlite";
    if (!copy(DB_PATH, $backupFile)) {
        throw new RuntimeException("Backup failed before migration to v{$targetVer}");
    }
    return $backupFile;
}
```

### 3.4 `migrations/v5_idol_refactor.php` — โครงสร้าง

```php
<?php
/**
 * Migration v5: Membership History + ID-Based Reference
 *
 * Schema:  + idol_memberships table
 *          + idol_entities.display_hint, drop UNIQUE on name
 *          + items.idol_id (FK)
 *
 * Backfill: 1 membership per existing parent_id
 *           items.idol_id where name → entity is unambiguous
 *
 * See MEMBERSHIP_HISTORY_PLAN.md for full design.
 */

function runMigrationV5(PDO $pdo): void {
    autoBackupBeforeMigration(5);
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->beginTransaction();
    try {
        v5_createMemberships($pdo);
        v5_recreateIdolEntities($pdo);
        v5_addItemsIdolId($pdo);
        v5_backfillMemberships($pdo);
        v5_backfillItemsIdolId($pdo);
        setSchemaVersion($pdo, 5);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw new RuntimeException("Migration v5 failed: " . $e->getMessage(), 0, $e);
    } finally {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
}

function v5_createMemberships(PDO $pdo): void { /* CREATE TABLE + indexes — ดู §2.1 */ }
function v5_recreateIdolEntities(PDO $pdo): void { /* drop UNIQUE + add display_hint — ดู §2.2 */ }
function v5_addItemsIdolId(PDO $pdo): void { /* ALTER TABLE + index — ดู §2.3 */ }
function v5_backfillMemberships(PDO $pdo): void { /* INSERT จาก parent_id */ }
function v5_backfillItemsIdolId(PDO $pdo): void { /* UPDATE items.idol_id non-ambiguous */ }
```

แยก function ย่อยตาม step ทำให้ test ทีละ step ง่าย + อ่าน flow ใน `runMigrationV5()` ครั้งเดียวเข้าใจ

### 3.5 Backfill Steps

**Step 4 — Memberships จาก parent_id** (idempotent ด้วย NOT EXISTS guard):

```sql
INSERT INTO idol_memberships (member_id, group_id, start_date, end_date, is_primary, note)
SELECT id, parent_id, NULL, NULL, 1, 'auto-migrated from parent_id'
FROM idol_entities e
WHERE category = 'member' AND parent_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM idol_memberships ms
      WHERE ms.member_id = e.id AND ms.note = 'auto-migrated from parent_id'
  );
```

**Step 5 — `items.idol_id` (เฉพาะ non-ambiguous):**

```sql
UPDATE items
SET idol_id = (
    SELECT e.id FROM idol_entities e
    WHERE e.name = items.idol AND e.category = 'member'
    GROUP BY e.name
    HAVING COUNT(*) = 1
)
WHERE idol_id IS NULL
  AND idol != '' AND idol != '-';
```

> เนื่องจาก `idol_entities.name` เดิมเป็น UNIQUE → ใน v1.4.x ทุกชื่อ match entity เดียวอยู่แล้ว → ในทางปฏิบัติ Step 5 จะ resolve ครบ 100% ตั้งแต่แรก ปัญหา ambiguous จะเกิดเมื่อ admin **สร้าง entity ใหม่ที่ชื่อซ้ำ** หลังจาก v1.5 เท่านั้น

### 3.6 Conflict Resolution UI

หน้า/panel ใน [idols.php](idols.php) — แสดงเมื่อมี `items.idol_id = NULL` ที่ name match หลาย entity:

```
┌─ Ambiguous mappings (3 cases) ─────────────────────────┐
│ Name: "Yuna" — items affected: 45                      │
│   Candidates:                                          │
│     ○ Yuna [ITZY]   (id=42)                            │
│     ○ Yuna [AKB48]  (id=87)                            │
│                                                        │
│   Action:                                              │
│     [ Map all to ▾ ]                                   │
│     [ Split by date: < 2025-01-01 → ITZY, ≥ → AKB48 ]  │
│     [ Map item-by-item ]                               │
└────────────────────────────────────────────────────────┘
```

API: `item_bulk_remap` รับ `idol_name`, `idol_id`, `date_from?`, `date_to?` (ดู §7.2)

### 3.7 Idempotency & Safety

- **Transaction wrap:** ถ้า step ใด fail → rollback ทั้งหมด, `schema_meta.version` ไม่ถูก stamp → run ใหม่ครั้งหน้าจะ migrate ใหม่ตั้งแต่ต้น
- **`PRAGMA foreign_keys = OFF`** ระหว่าง recreate `idol_entities` (เปิดกลับใน `finally`)
- **`NOT EXISTS` guard** ใน Step 4 → ถ้า process ตายระหว่าง migration (schema_meta ยังไม่ stamp) แต่ insert ไปบางส่วนแล้ว → re-run จะไม่ duplicate
- **Column check ก่อน ALTER TABLE** (SQLite ไม่มี `ADD COLUMN IF NOT EXISTS`):
  ```php
  $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
  if (!in_array('idol_id', $cols, true)) {
      $pdo->exec('ALTER TABLE items ADD COLUMN idol_id INTEGER REFERENCES idol_entities(id) ON DELETE SET NULL');
  }
  ```

### 3.8 Optional: CLI Wrapper

สำหรับ admin ที่อยาก run migration อย่าง controlled (เช่นใน Docker exec) เพิ่ม `migrate.php` ที่ root:

```php
<?php
// migrate.php — CLI only entry point for explicit migration
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/config.php';   // initDB() จะ trigger migration อัตโนมัติ
$pdo = getDB();
echo "Schema version: " . getSchemaVersion($pdo) . "\n";
```

ใช้: `docker exec numa-log php migrate.php` — ได้ feedback ชัด ไม่ต้องรอเปิด browser

> **ไม่บังคับ** — auto-trigger ผ่าน `initDB()` ก็พอ; CLI wrapper เป็นแค่ convenience สำหรับ ops

### 3.9 Rollback

**Option A (preferred):** Restore จาก `data/backups/pre-v5-{timestamp}.sqlite` ผ่าน [backup.php](backup.php) UI หรือ copy ไฟล์ตรง ๆ → revert code v1.4.x

**Option B (manual SQL):** drop `idol_memberships`, recreate `items` table โดยไม่มี `idol_id` (SQLite ไม่ DROP COLUMN ตรง), recreate `idol_entities` ด้วย UNIQUE บน name, `UPDATE schema_meta SET value='4' WHERE key='version'`

แนะนำ Option A เสมอ — เร็ว predictable

---

## 4. Backend Helper Functions

เพิ่มใน `config.php` (หรือไฟล์ใหม่ `helpers_idol.php`):

```php
/** Resolve group_id ของ member ณ วันที่ (คืน null ถ้าไม่มี membership) */
function resolveMemberGroup(PDO $pdo, int $memberId, string $date): ?int { ... }

/**
 * Resolve idol_id จาก name (สำหรับ import / legacy data)
 * Return: ['id' => int|null, 'ambiguous' => bool, 'candidates' => array]
 */
function resolveIdolByName(PDO $pdo, string $name): array { ... }

/** Display: "Yuna [ITZY]" หรือ "Yuna" ถ้าไม่มี hint */
function formatIdolDisplay(array $entity): string {
    return $entity['display_hint'] !== ''
        ? sprintf('%s [%s]', $entity['name'], $entity['display_hint'])
        : $entity['name'];
}
```

---

## 5. Report Query Changes

### 5.1 Pattern ใหม่: ID-based + Time-based join

**ของเดิม (v1.4.x):**
```sql
JOIN idol_entities m ON m.name = i.idol AND m.category = 'member'
LEFT JOIN idol_entities p ON m.parent_id = p.id
```

**ของใหม่ (v1.5):**
```sql
JOIN idol_entities m       ON m.id = i.idol_id AND m.category = 'member'
LEFT JOIN idol_memberships ms ON ms.member_id = m.id
    AND ms.is_primary = 1
    AND (ms.start_date IS NULL OR ms.start_date <= i.order_date)
    AND (ms.end_date   IS NULL OR ms.end_date   >= i.order_date)
LEFT JOIN idol_entities p  ON p.id = ms.group_id          -- group ณ วันซื้อ
LEFT JOIN idol_entities gp ON gp.id = p.parent_id         -- company ของ group
```

> Items ที่ `idol_id IS NULL` จะไม่ปรากฏใน report (เหมือนเดิมที่ filter `idol != '' AND idol != '-'`)

### 5.2 ฟังก์ชันใน [api.php](api.php) ที่ต้องแก้

| Function | บรรทัด | สิ่งที่ต้องแก้ |
|----------|--------|----------------|
| `handleReportMonthly()` | ~246 | by_idol section: GROUP BY `m.id` แทน `i.idol` |
| `handleReportIdol()` | ~279 | GROUP BY `m.id`; return `idol_id` + `display` |
| `handleReportIdolDetail()` | ~329 | รับ `idol_id` (ไม่ใช่ `idol` name); query ใช้ `i.idol_id = :id` |
| `handleReportByGroup()` | ~369 | เลิก recursive `collectNames()`; aggregate ผ่าน `idol_memberships` |
| `handleReportByCompany()` | ~483 | aggregate ผ่าน membership → group → company |
| `handleTypeByMembers()` | ~678 | join membership ตาม `i.order_date`; GROUP BY `i.type, m.id` |
| `handleReportTypeDetail()` | ~732 | เหมือนข้างบน |
| `handleIdolEntitiesTree()` | ~575 | stats ใช้ `idol_id` แทน name; รวม items ของ entity ที่ชื่อซ้ำได้ถูก |

### 5.3 Frontend payload changes

API response เพิ่ม `idol_id` + `display` ทุกที่ที่มี `idol`:

```json
{
  "idol": "Yuna",
  "idol_id": 42,
  "display": "Yuna [ITZY]",
  "items": 30,
  "total_qty": 45,
  "total_price": 12500.00
}
```

[index.php](index.php) URL pre-fill:
- ใหม่: `?idol_id=42`
- เก่า: `?idol=Yuna` ยัง support (resolve เป็น id; ถ้า ambiguous → แสดง filter chip "Yuna (multiple)" + ให้เลือก)

---

## 6. Frontend Changes

### 6.1 [idols.php](idols.php) — Membership Panel + Display Hint

ใน entity form เพิ่มฟิลด์:

```
Name:         [ Yuna                          ]
Category:     [ Member ▾ ]
Parent:       [ ITZY (default group) ▾ ]
Display hint: [ ITZY                          ]   ← ใหม่ — แนะนำให้ใส่ถ้าชื่อซ้ำ
Sort order:   [ 0 ]
```

**Soft suggest สำหรับ `display_hint`:** เมื่อ user พิมพ์ชื่อแล้ว blur ออก ระบบเช็คว่ามี entity ชื่อเดียวกันอยู่หรือยัง:
- ถ้ามี → popup เตือน "There's already an entity named 'Yuna' — consider adding a display hint to distinguish them" + auto-fill `display_hint` จาก parent ที่กำลังเลือก (user แก้/ลบได้, ไม่ block save)
- ถ้าไม่มี → ไม่ทำอะไร

หลังบันทึก → ถ้า member มี parent → สร้าง membership default (NULL start/end, is_primary=1)

ใน member detail page เพิ่ม section "Memberships" (จากแผนเดิม):

```
┌─ Memberships ──────────────────────────────────────────┐
│ Group         | Start       | End         | Primary | │
│ NMB48         | -           | 2025-07-31  |   ✓     |🗑│
│ AKB48 Team A  | 2025-08-01  | -           |   ✓     |🗑│
│                                                        │
│ [+ Add membership]  [Move to new group →]              │
└────────────────────────────────────────────────────────┘
```

### 6.2 [index.php](index.php) — Item Add/Edit Form

Dropdown "Idol" เปลี่ยนจาก searchable text → select option ที่ value = `idol_id`:

```html
<select name="idol_id">
  <option value="42">Yuna [ITZY]</option>
  <option value="87">Yuna [AKB48]</option>
  ...
</select>
```

- Search-as-you-type (เหมือนเดิม) แต่ค่าที่ส่งเป็น `idol_id`
- Server save: ใช้ `idol_id` lookup เพื่อ store ทั้ง `idol_id` (FK) + `idol` (text snapshot)
- กรณีพิมพ์ชื่อใหม่ที่ยังไม่มี entity → ปุ่ม "Create new member: ..." (เปิด modal เลือก group + display_hint)

### 6.3 Tree View ใน idols.php

- ยังเรนเดอร์ตาม `parent_id` (เป็น default group)
- Member ที่มี membership history > 1 → icon 🔄 ข้างชื่อ
- Member ที่ชื่อซ้ำกับ entity อื่น → แสดงเป็น `Yuna [ITZY]` ตาม `display_hint`

### 6.4 หน้า/Panel Conflict Resolution

- Badge ใน [idols.php](idols.php): "⚠ 3 ambiguous mappings" (admin only)
- Click → panel ตาม section 3.3

### 6.5 Unmapped Panel
- เดิม: idol name ใน items ที่ไม่ match entity ไหนเลย → "Quick add"
- ใหม่: + แสดง "Ambiguous" group แยก (match หลาย entity → ต้อง resolve)

---

## 7. API Endpoints

### 7.1 Membership (จากแผนเดิม)
| Action | Method | Params | Response |
|--------|--------|--------|----------|
| `membership_list` | GET | `member_id` | `[{id, group_id, group_name, start_date, end_date, is_primary, note}]` |
| `membership_save` | POST | `id?, member_id, group_id, start_date?, end_date?, is_primary, note` | `{success, id}` |
| `membership_delete` | POST | `id` | `{success}` |
| `membership_move` | POST | `member_id, new_group_id, move_date` | ปิด membership ปัจจุบัน + สร้างใหม่ |

### 7.2 ใหม่: Idol mapping
| Action | Method | Params | Response |
|--------|--------|--------|----------|
| `idol_search` | GET | `q, category?` | `[{id, name, display_hint, display, members_count?}]` |
| `idol_resolve_name` | GET | `name` | `{id, ambiguous, candidates: [...]}` |
| `item_remap` | POST | `item_id, idol_id` | `{success}` |
| `item_bulk_remap` | POST | `idol_name, idol_id, date_from?, date_to?` | `{success, updated}` |
| `ambiguous_list` | GET | - | `[{name, items_count, candidates}]` |

### 7.3 Validation & `item_save` policy (Hybrid)

**`item_save` รับได้ทั้ง `idol_id` (preferred) และ `idol` text (fallback)** — เพื่อรองรับทั้ง frontend (ส่ง id จาก dropdown), Excel import, และ external script ในอนาคต

Resolution path:
1. ถ้ามี `idol_id` → ใช้ตรง ๆ + auto-fill `idol` text จาก `entity.name` (snapshot)
2. ถ้าไม่มี `idol_id` แต่มี `idol` text → resolve ด้วย `resolveIdolByName()`:
   - Match 1 entity → save
   - Ambiguous (match หลาย entity) → return HTTP **409** + array of candidates → frontend แสดง picker
   - No match → return HTTP **422** + suggest "create new entity" → frontend เปิด modal create
3. ไม่มีทั้งคู่ → HTTP 400

Other validations:
- `items.idol_id` ต้อง refer entity ที่ `category = 'member'`
- `idol_memberships` — **warn (ไม่ block)** เมื่อมี primary overlap → return `{success: true, warnings: [...]}` ให้ UI แสดง toast/banner

---

## 8. Edge Cases & Decisions

| Case | Policy |
|------|--------|
| `idol_id IS NULL` (resolve ไม่ได้) | แสดงใน report เป็น row **"(Unassigned)"** (รวม spending ที่ resolve ไม่ได้); flag ใน unmapped/ambiguous panel ให้ admin map |
| สร้าง entity ใหม่ชื่อซ้ำโดยไม่ใส่ display_hint | Soft suggest popup เตือน + auto-fill จาก parent (ไม่ block save) |
| Import Excel idol name ซ้ำ | **Partial commit** — row ที่ resolved → import เลย, row ที่ ambiguous → queue + banner เตือน |
| Export Excel | include `idol` (display) + `idol_id` (column hidden/optional) |
| Delete entity ที่มี items refer | `ON DELETE SET NULL` → items กลายเป็น unmapped (UI เตือนก่อน delete + นับจำนวน items affected) |
| Member ไม่มี membership ณ วันที่ของ item | report เป็น `"(Unassigned)"` (fallback ไป `parent_id` ของ entity ถ้ามี) |
| Member อยู่หลายวง (sub-unit) | `is_primary=0` สำหรับ sub-unit; By Group หลักนับเฉพาะ primary; **sub-unit ดูได้ผ่าน drill-down report ใน v1.5** |
| Primary membership overlap | **Warn (ไม่ block)** — return warning ใน response, UI แสดง toast เตือน, save ต่อได้ |
| Items `order_date` ว่าง | ใช้ membership ปัจจุบัน (end_date IS NULL) |
| Entity ถูก rename ภายหลัง | `items.idol` (text) **ไม่อัปเดต** — เก็บเป็น snapshot ของชื่อเดิม ณ วันที่ซื้อ; report ใช้ `entity.name` ปัจจุบันสำหรับ display |
| Re-seed idol data | ต้องไม่ลบ memberships ที่ user สร้าง / preserve idol_id ของ items |

---

## 9. Phased Rollout

### Phase 1 — Schema Migration (v1.5.0-alpha)
- [ ] สร้างโฟลเดอร์ `migrations/` + `README.md` อธิบายระบบ versioning
- [ ] `migrations/_helpers.php` — `getSchemaVersion`, `setSchemaVersion`, `autoBackupBeforeMigration`
- [ ] เพิ่ม migration trigger ใน `config.php` initDB() (~6 บรรทัด)
- [ ] `migrations/v5_idol_refactor.php` — `runMigrationV5()` + 5 helper functions
  - [ ] `v5_createMemberships` — CREATE TABLE + indexes
  - [ ] `v5_recreateIdolEntities` — drop UNIQUE + add `display_hint`
  - [ ] `v5_addItemsIdolId` — ALTER TABLE + index (with column check)
  - [ ] `v5_backfillMemberships` — INSERT จาก parent_id (with NOT EXISTS guard)
  - [ ] `v5_backfillItemsIdolId` — UPDATE non-ambiguous
- [ ] (Optional) `migrate.php` CLI wrapper
- [ ] Test: idempotent migration, fresh install, upgrade path, rollback simulation

### Phase 2 — Backend Refactor (v1.5.0-beta)
- [ ] Helpers (`resolveMemberGroup`, `resolveIdolByName`, `formatIdolDisplay`)
- [ ] Refactor 8 functions ใน api.php → ID-based + time-based join
- [ ] เพิ่ม endpoints: idol_search, item_remap, item_bulk_remap, ambiguous_list
- [ ] เพิ่ม membership endpoints (list/save/delete/move)
- [ ] Integration test: เทียบ report output ก่อน/หลัง (DB snapshot)

### Phase 3 — UI Refactor (v1.5.0-rc)
- [ ] Membership panel ใน [idols.php](idols.php) + ปุ่ม "Move to new group" shortcut
- [ ] `display_hint` ใน entity form + **soft suggest** เมื่อชื่อซ้ำ
- [ ] Item form: dropdown แสดง `display_hint` + send `idol_id`
- [ ] Conflict resolution panel (ambiguous mappings) — Map all / Split by date / Map item-by-item
- [ ] Tree view: icon 🔄 + display name disambiguated
- [ ] **Sub-unit drill-down report**: ใน By Group/By Member detail page เพิ่ม tab "Sub-units" แสดง breakdown ตาม non-primary memberships ของ member ใน group
- [ ] Banner "⚠ N items pending resolution" ใน [index.php](index.php) (link ไปหน้า resolve)

### Phase 4 — Import / Export / Docs (v1.5.0)
- [ ] Excel import: รองรับ `idol_id` column, auto-resolve, **partial commit + queue** สำหรับ ambiguous
- [ ] Excel export: เพิ่ม `idol_id` column (optional, hidden by default)
- [ ] อัปเดต [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md), [API_ENDPOINTS.md](API_ENDPOINTS.md), [HOW_TO_USE.md](HOW_TO_USE.md), [HOW_TO_USE_EN.md](HOW_TO_USE_EN.md), [CHANGELOG.md](CHANGELOG.md), [README.md](README.md)

### Phase 5 — Cleanup back-compat paths (v1.5.1+, optional)
- [ ] ลบ fallback path ใน query (เช่น `if hasMembership ... else ...`)
- [ ] Deprecate `idol_entities.parent_id` ใน query layer — ใช้แค่เป็น UI cache สำหรับ tree view
- [ ] **`items.idol` (text) คงไว้ตลอด** เป็น immutable snapshot — ไม่ drop

---

## 10. Testing Plan

### Migration tests (Phase 1)
1. Fresh install → schema version = 5, ทุกตารางถูกสร้าง
2. Upgrade จาก v1.4.x (DB snapshot จริง) → backfill ทำงาน, version stamp ถูก
3. Re-run `initDB()` → idempotent (ไม่ duplicate, ไม่ error)
4. Rollback simulation → restore from auto-backup → ระบบกลับมาทำงานปกติ

### Duplicate name tests (Phase 2-3)
5. สร้าง 2 entity ชื่อ "Yuna" (ITZY, AKB48) → display ใน dropdown เห็น `[ITZY]` / `[AKB48]`
6. Import items "Yuna" → ambiguous queue ทำงาน
7. Bulk remap by date range → items แยก idol_id ถูก
8. Report By Member → "Yuna [ITZY]" และ "Yuna [AKB48]" แยกแถวกัน

### Membership history tests (Phase 2-3)
9. Member ย้ายวง: items ก่อน/หลัง → By Group ถูก (ไม่นับซ้ำ)
10. Multi-group concurrent: primary นับใน By Group หลัก, sub-unit ไม่นับ
11. No membership at date → "(Unassigned)"
12. Primary overlap validation → reject HTTP 400
13. "Move" shortcut → membership เก่า set `end_date = move_date - 1`, ใหม่ `start_date = move_date`

### End-to-end (Phase 4)
14. Import Excel → Conflict UI → Resolve → Report → Export → Restore → ผลรายงานเท่าเดิม
15. Re-seed → ไม่ทำลาย memberships / idol_id ของ items

---

## 11. Risks & Mitigation

| Risk | Mitigation |
|------|------------|
| Migration ผิด → ข้อมูลเพี้ยน | Auto-backup ก่อน migrate; migration ใน transaction; version marker |
| Recreate `idol_entities` ทำให้ FK refs จาก `idol_memberships` หาย | ทำใน transaction เดียว, `foreign_keys=OFF` ชั่วคราว, recreate ตามลำดับ |
| `ALTER TABLE ADD COLUMN` ช้า | SQLite ADD COLUMN เป็น O(1) (metadata only) — ไม่ใช่ปัญหา |
| Ambiguous items ค้างใน queue, user ลืม resolve → report ขาด | Banner ใน [index.php](index.php) แสดง count + ลิงก์ไปแก้ |
| Excel import เก่าไม่มี `idol_id` column | Auto-resolve ตาม name (เหมือนเดิม) → queue ambiguous |
| Query ช้าลงเพราะ join เพิ่ม | Indexes บน `idol_id`, `(member_id, start_date, end_date)`; EXPLAIN QUERY PLAN |
| URL params เก่า `?idol=Yuna` → ambiguous | Resolve → ถ้า 1 match: redirect to `?idol_id=...`; ถ้าหลาย: แสดง picker |
| User สร้าง entity ใหม่ที่ชื่อซ้ำ ของเดิม → items เก่า re-map ผิด | items.idol_id ที่ resolve ไปแล้วจะไม่เปลี่ยน; ตรวจเฉพาะของใหม่ |

---

## 12. Decisions

ตัดสินใจแล้วทั้ง 8 ข้อ (รวมเข้า scope ของ v1.5):

| # | คำถาม | คำตอบ | สะท้อนใน section |
|---|---|---|---|
| 1 | Fallback ของ unresolved items | **"(Unassigned)" row** ใน report (รวม spending ที่ resolve ไม่ได้) | §2.3, §5, §8 |
| 2 | `is_primary` overlap | **Warn (loose)** — return warning ใน response, ไม่ block save | §7.3, §8 |
| 3 | Sub-unit drill-down | **ทำใน v1.5 (Phase 3)** — แสดงเป็น tab ใน group/member detail | §9 Phase 3 |
| 4 | `items.group_id_override` | **ไม่เพิ่มใน v1.5** — รอ user feedback, ค่อยเพิ่มแบบ additive ใน v1.6 ถ้าจำเป็น | §1 Non-goals |
| 5 | `display_hint` auto-suggest | **Soft suggest** — popup เตือนเมื่อชื่อซ้ำ + auto-fill จาก parent group, user แก้/ลบได้ (ไม่ block) | §6.1, §9 Phase 3 |
| 6 | Drop `items.idol` text | **ไม่ drop เลย** — เก็บเป็น immutable snapshot ของชื่อ ณ วันที่ซื้อ (กัน "amnesia" เวลา entity rename/delete) | §1 Non-goals, §2.3, §9 Phase 5 |
| 7 | Excel import ambiguous | **Partial commit + queue** — row resolved → import ทันที, row ambiguous → queue + banner | §8, §9 Phase 4 |
| 8 | `item_save` API | **Hybrid** — preferred `idol_id`, fallback `idol` text + auto-resolve; ambiguous → 409 + candidates; no match → 422 + create suggestion | §7.3 |

---

## 13. Documentation Updates

- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) — เพิ่ม `idol_memberships`, ปรับ `idol_entities` (drop UNIQUE, add `display_hint`), ปรับ `items` (add `idol_id`)
- [API_ENDPOINTS.md](API_ENDPOINTS.md) — เพิ่ม 9 endpoints (membership x4 + mapping x5)
- [HOW_TO_USE.md](HOW_TO_USE.md) / [HOW_TO_USE_EN.md](HOW_TO_USE_EN.md) — section Idol Management: เพิ่มเรื่อง membership + handling duplicate names
- [CHANGELOG.md](CHANGELOG.md) — entry v1.5.0
- [README.md](README.md) — bump version + feature list

---

## 14. Estimated Effort

| Phase | งาน | เวลาประมาณ |
|-------|-----|------------|
| 1 | Schema migration + backfill + version system + tests | 4–6 ชม. |
| 2 | Helpers + refactor 8 queries + 9 endpoints + tests | 8–10 ชม. |
| 3 | UI: membership panel + item form + conflict UI + display hint (soft suggest) + tree + **sub-unit drill-down** + pending banner | 12–16 ชม. |
| 4 | Excel import/export + docs + manual QA | 4–6 ชม. |
| 5 | Cleanup (optional, later release) | 2–3 ชม. |
| **รวม** | (Phase 1–4) | **~28–38 ชม.** |
