# แผน: ระบบงบประมาณ & เป้าหมายการใช้จ่าย (Budget / Spending Goals) — v1.8.0

> สถานะ: รออนุมัติ / เริ่มลงมือ
> เป้าหมายเวอร์ชัน: **1.8.0** (เพิ่ม schema v6)

## สรุปดีไซน์ (จากที่ยืนยันกับผู้ใช้)

- **Scope:** ครบทุกระดับ — Overall / Type / Group / Company / Member (ตารางเดียว แยกด้วย `scope_type` + `scope_ref`)
- **Period:** รายเดือนแบบวนซ้ำ (recurring monthly) — งบเป็นค่าคงที่ต่อเดือน เทียบกับยอดของเดือนที่เลือก (ดีฟอลต์ = เดือนปัจจุบัน)
- **แสดงผล 3 จุด:** หน้าใหม่ `budget.php`, การ์ดบน Dashboard, แท็บใหม่ในรายงาน
- **เกินงบ:** เตือนด้วยสีเท่านั้น (ไม่บล็อกการบันทึก item) — และ **ตั้ง threshold สีได้เองต่อ budget**:
  - เขียว (ok): `pct < warn_pct`
  - เหลือง (near): `warn_pct ≤ pct < danger_pct`
  - แดง (over): `pct ≥ danger_pct`
  - ดีฟอลต์ `warn_pct = 80`, `danger_pct = 100`

---

## 1. Schema — Migration v6

สร้าง `migrations/v6_budgets.php` ตามแพตเทิร์น v5 (autoBackupBeforeMigration → transaction → DDL → setSchemaVersion):

```sql
CREATE TABLE budgets (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    scope_type     TEXT NOT NULL DEFAULT 'overall'
                   CHECK(scope_type IN ('overall','type','group','company','member')),
    scope_ref_id   INTEGER NULL,      -- idol_entities.id (group/company/member); NULL = overall/type
    scope_ref_name TEXT DEFAULT '',   -- type name (scope='type') หรือ label snapshot
    amount         REAL NOT NULL DEFAULT 0,     -- ลิมิตต่อเดือน (฿)
    warn_pct       INTEGER NOT NULL DEFAULT 80, -- threshold เหลือง
    danger_pct     INTEGER NOT NULL DEFAULT 100,-- threshold แดง
    note           TEXT DEFAULT '',
    is_active      INTEGER NOT NULL DEFAULT 1,
    created_at     TEXT DEFAULT (datetime('now','localtime'))
);
CREATE INDEX idx_budgets_scope ON budgets(scope_type, scope_ref_id);
```

- กันงบซ้ำด้วย partial unique index บน `(scope_type, scope_ref_id, scope_ref_name)` เฉพาะ `is_active=1` (หรือเช็คในโค้ด)
- `member`/`group`/`company` อ้างด้วย `scope_ref_id` (ตาม gotcha: อย่าอ้างด้วยชื่อ) — เก็บ `scope_ref_name` เป็น snapshot กันกรณี entity ถูกลบ
- bump `DB_SCHEMA_VERSION` → `6` และ wire `runMigrationV6` ใน `initDB()` ของ `config.php`
- **Validation thresholds:** บังคับ `1 ≤ warn_pct ≤ danger_pct ≤ 1000` (อนุญาตเกิน 100 ได้เผื่ออยากตั้งแดงสูงกว่าลิมิต) ทั้งฝั่ง JS และ API

---

## 2. API — เพิ่ม actions ใน `api.php`

เพิ่มใน `match`: `budget_list`, `budget_save`, `budget_delete`, `budget_progress`

| Handler | หน้าที่ |
|---|---|
| `handleBudgetList` | คืนนิยามงบทั้งหมด + ยอดใช้จ่ายเดือนปัจจุบัน + สถานะสี (ใช้หน้า budget.php) |
| `handleBudgetSave` | create/update (validate `scope_type`, `amount ≥ 0`, `1 ≤ warn_pct ≤ danger_pct`; `verifyCsrf()` ทำที่ต้นไฟล์อยู่แล้ว) |
| `handleBudgetDelete` | ลบตาม id |
| `handleBudgetProgress` | รับ `?month=YYYY-MM` (ดีฟอลต์เดือนนี้) คืน spent/remaining/pct/status ต่อ budget — ใช้ทั้ง Dashboard card และแท็บรายงาน |

**การคำนวณ spent ต่อ scope** (กรองด้วย `strftime('%Y-%m', order_date) = :month`):

- `overall` → `SUM(price_per_qty*qty)` ทั้งหมดในเดือน
- `type` → `WHERE type = scope_ref_name`
- `member` → `WHERE idol_id = scope_ref_id`
- `group` / `company` → reuse logic ของ `handleReportByGroup` / `handleReportByCompany` (join `idol_memberships` ด้วย `is_primary=1` และ `order_date BETWEEN start_date AND end_date`)

**สถานะสี (คำนวณฝั่ง server แล้วส่ง `status`):**
`pct = spent/amount*100` →
- `ok` เมื่อ `pct < warn_pct`
- `near` เมื่อ `warn_pct ≤ pct < danger_pct`
- `over` เมื่อ `pct ≥ danger_pct`

---

## 3. หน้าใหม่ `budget.php`

โครงตาม `types.php` (navbar + CSRF fetch wrapper + modal CRUD + i18n):

- ตารางงบ: scope (badge สี), amount, ยอดใช้จ่ายเดือนนี้, คงเหลือ, **progress bar** เปลี่ยนสีตามสถานะ (เขียว/เหลือง/แดง)
- ตัวเลือกเดือน (`<input type="month">`) เพื่อดูย้อนหลัง
- Modal form:
  - เลือก `scope_type` → ถ้า `type` เลือกจาก `type_list`; ถ้า `group`/`company`/`member` ใช้ `idol_search` (มีอยู่แล้ว)
  - ช่อง `amount`
  - ช่อง `warn_pct` (เหลือง) + `danger_pct` (แดง) — มี hint แสดงตัวอย่างสี
  - ช่อง `note`
- สิทธิ์: เปิดให้ทั้ง admin และ user (เหมือน types/idols) — งบเป็นข้อมูลส่วนตัวของ tracker

---

## 4. การ์ดบน Dashboard `index.php`

- เพิ่มแถวการ์ด "งบเดือนนี้" ใต้ KPI: ดึง `budget_progress` ของเดือนปัจจุบัน
- แสดง progress bar ของงบ overall (ถ้ามี) + รายการงบที่ near/over เป็น highlight
- ถ้ายังไม่ตั้งงบ → แสดงลิงก์ไป budget.php

---

## 5. แท็บใหม่ในรายงาน `report.php`

- เพิ่มแท็บที่ 14 `tabBudget` (icon `bi-wallet2`) — lazy-load ตอนคลิกครั้งแรก (ตามแพตเทิร์น tab อื่น)
- แสดงงบทุก scope พร้อม progress + ตัวเลือกเดือนให้ดูย้อนหลัง

---

## 6. Navbar — เพิ่มลิงก์ Budget

เพิ่มลิงก์ `budget.php` (icon `bi-piggy-bank`) ใน navbar ของทุกหน้า: `index`, `items`, `report`, `idols`, `types`, `budget` (navbar ถูก duplicate ในแต่ละไฟล์)

---

## 7. i18n — `lang/en.php` + `lang/th.php`

เพิ่ม key กลุ่ม `budget.*`: `nav.budget`, ชื่อหน้า, scope labels, amount/warn_pct/danger_pct/remaining/spent, สถานะ (ok/near/over), ข้อความ modal, ข้อความ no-budget — เพิ่ม EN ก่อน แล้ว TH overlay

---

## 8. เอกสาร & เวอร์ชัน

- `APP_VERSION` → **1.8.0** ใน `config.php`
- **`README.md`:** อัพเดท `**Latest:**` (ตามที่เคยกำชับไว้ — บังคับทุกครั้งที่ bump version)
- `CLAUDE.md`: schema v6, ตาราง budgets, `budget.php` ใน Key Files, แท็บที่ 14, Role Permissions, constant
- `DATABASE_SCHEMA.md` + `API_ENDPOINTS.md`: เพิ่มตาราง/endpoint ใหม่

---

## ลำดับการทำงาน

1. Migration v6 + bump schema/version + wire ใน config.php
2. API handlers (4 actions)
3. budget.php (CRUD + progress + threshold สี)
4. i18n keys (en + th)
5. Dashboard card + Report tab
6. Navbar links ทุกหน้า
7. เอกสาร (README, CLAUDE, SCHEMA, ENDPOINTS)
8. ทดสอบ: `php -S localhost:8000` → ตั้งงบแต่ละ scope → เช็คสี (เขียว/เหลือง/แดงตาม threshold)/ยอด/เดือนย้อนหลัง

---

## จุดที่ต้องระวัง

- Migration ต้อง backward-compatible (ตาราง budgets ว่างได้)
- spent ของ group/company ต้องใช้ membership join ให้ตรงกับรายงานเดิม
- `items.idol` เป็น snapshot — งบ member/group ต้องอ้าง `idol_id`/membership เท่านั้น (ห้ามอ้างด้วยชื่อ)
- threshold: บังคับ `warn_pct ≤ danger_pct` เพื่อกันลำดับสีสลับ
