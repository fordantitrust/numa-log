# Cloudflare Serverless Migration Plan

> แผนการ migrate **numa-log** จาก PHP + SQLite แบบ traditional hosting ไปเป็น serverless บน Cloudflare

---

## ภาพรวม: Stack เปรียบเทียบ

| Component | ปัจจุบัน | Cloudflare (ใหม่) |
|-----------|----------|-------------------|
| Runtime | PHP | TypeScript (Cloudflare Workers) |
| Database | SQLite ไฟล์บนดิสก์ | Cloudflare D1 (SQLite-compatible) |
| Authentication | PHP Sessions | JWT (stateless) หรือ Cloudflare KV |
| File Storage | Filesystem | Cloudflare R2 (object storage) |
| Excel Processing | PhpSpreadsheet | SheetJS (JavaScript) |
| Frontend Pages | PHP-rendered HTML | Static HTML/JS (Cloudflare Pages) |
| Hosting | Apache/Nginx + PHP | Cloudflare Workers + Pages |

---

## Architecture ใหม่

```
┌─────────────────────────────────────┐
│  Cloudflare Pages                   │
│  (Static HTML/CSS/JS frontend)      │
│  index.html, report.html, ...       │
└──────────────┬──────────────────────┘
               │ fetch /api/*
┌──────────────▼──────────────────────┐
│  Cloudflare Workers (TypeScript)    │
│  src/index.ts  (router)             │
│  src/routes/items.ts                │
│  src/routes/auth.ts                 │
│  src/routes/backup.ts               │
│  src/routes/export.ts               │
└──────┬──────────────┬───────────────┘
       │              │
┌──────▼───┐    ┌─────▼──────┐
│  D1      │    │  R2        │
│ (SQLite) │    │ (Backups,  │
│          │    │  Exports)  │
└──────────┘    └────────────┘
```

---

## สิ่งที่ต้องทำ (ทีละส่วน)

### 1. เปลี่ยน Runtime: PHP → TypeScript

Cloudflare Workers รัน JavaScript/TypeScript หรือ WebAssembly เท่านั้น PHP ไม่สามารถรันได้โดยตรง ต้อง rewrite ทุกไฟล์:

| ไฟล์เดิม | ทดแทนด้วย |
|----------|-----------|
| `config.php` | `src/lib/db.ts` + wrangler env bindings |
| `api.php`, `api_users.php` | Worker routes ใน `src/routes/*.ts` |
| `index.php`, `report.php`, `idols.php`, ฯลฯ | Static HTML + JavaScript (fetch API) |
| `login.php` | Static `login.html` + Worker `/api/login` endpoint |
| `backup.php` | Worker + R2 integration |
| `export.php` | Worker + SheetJS |
| `import.php` | Worker + SheetJS |

---

### 2. เปลี่ยน Database: SQLite ไฟล์ → Cloudflare D1

D1 เป็น SQLite-compatible ทำให้ schema แทบไม่ต้องเปลี่ยน แต่วิธีเชื่อมต่อเปลี่ยนทั้งหมด

**เดิม (PHP + PDO):**
```php
$pdo = new PDO('sqlite:' . DB_PATH);
$stmt = $pdo->prepare("SELECT * FROM items WHERE idol = ?");
$stmt->execute([$idol]);
$rows = $stmt->fetchAll();
```

**ใหม่ (TypeScript + D1):**
```typescript
const result = await env.DB.prepare("SELECT * FROM items WHERE idol = ?")
  .bind(idol)
  .all();
const rows = result.results;
```

**ขั้นตอน:**
1. สร้าง D1: `wrangler d1 create numa-log-db`
2. Export schema จาก `config.php` เป็น `.sql` migration file
3. รัน migration: `wrangler d1 execute numa-log-db --file=migrations/0001_init.sql`
4. Bind D1 ใน `wrangler.toml`:
```toml
[[d1_databases]]
binding = "DB"
database_name = "numa-log-db"
database_id = "<your-database-id>"
```

---

### 3. เปลี่ยน Auth: PHP Sessions → JWT

PHP `session_start()` และ `$_SESSION` ไม่มีใน Workers เพราะ stateless

**เดิม (PHP Sessions):**
```php
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
```

**ใหม่ (JWT):**
```typescript
// Sign token
const token = await signJWT(
  { userId: user.id, role: user.role },
  env.JWT_SECRET
);
// Return as HttpOnly cookie
return new Response(JSON.stringify({ ok: true }), {
  headers: { 'Set-Cookie': `session=${token}; HttpOnly; SameSite=Strict; Path=/` }
});
```

**สิ่งที่ต้องเปลี่ยน:**
- `password_hash()` / `password_verify()` → ใช้ bcrypt via WASM หรือ Web Crypto API (`SubtleCrypto`)
- CSRF token (ที่เก็บใน session) → เปลี่ยนเป็น **Double-Submit Cookie** pattern หรือใช้ `SameSite=Strict` + Origin header check
- Session timeout → ใส่ `exp` claim ใน JWT payload

---

### 4. เปลี่ยน File Storage: Disk → Cloudflare R2

**เดิม (ไฟล์บนดิสก์):**
```php
copy(DB_PATH, BACKUP_DIR . '/backup_' . date('Ymd_His') . '.sqlite');
```

**ใหม่ (R2):**
```typescript
// Upload backup
const key = `backup_${timestamp}.sqlite`;
await env.BACKUPS.put(key, dbExportBuffer);

// List backups
const list = await env.BACKUPS.list();

// Download backup
const file = await env.BACKUPS.get(key);
```

**ขั้นตอน:**
1. สร้าง R2 bucket: `wrangler r2 bucket create numa-log-backups`
2. Bind R2 ใน `wrangler.toml`:
```toml
[[r2_buckets]]
binding = "BACKUPS"
bucket_name = "numa-log-backups"
```

---

### 5. เปลี่ยน Excel: PhpSpreadsheet → SheetJS

**เดิม (PHP):**
```php
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Title');
```

**ใหม่ (TypeScript + SheetJS):**
```typescript
import * as XLSX from 'xlsx';

// Export
const wb = XLSX.utils.book_new();
const ws = XLSX.utils.json_to_sheet(rows);
XLSX.utils.book_append_sheet(wb, ws, 'Items');
const buffer = XLSX.write(wb, { type: 'buffer', bookType: 'xlsx' });

// Import
const workbook = XLSX.read(await file.arrayBuffer());
const rows = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);
```

---

### 6. Security Headers

**เดิม (PHP):**
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
```

**ใหม่ (TypeScript):**
```typescript
function addSecurityHeaders(response: Response): Response {
  const headers = new Headers(response.headers);
  headers.set('X-Content-Type-Options', 'nosniff');
  headers.set('X-Frame-Options', 'DENY');
  headers.set('X-XSS-Protection', '1; mode=block');
  return new Response(response.body, { ...response, headers });
}
```

---

## โครงสร้างโปรเจกต์ใหม่

```
numa-log-cf/
├── wrangler.toml               # Cloudflare config
├── package.json
├── tsconfig.json
├── migrations/
│   └── 0001_init.sql           # D1 schema (export จาก config.php)
├── src/
│   ├── index.ts                # Worker entry point + router
│   ├── lib/
│   │   ├── auth.ts             # JWT sign/verify, password hashing
│   │   ├── csrf.ts             # CSRF protection
│   │   ├── headers.ts          # Security headers
│   │   └── response.ts         # Helper: jsonResponse()
│   └── routes/
│       ├── items.ts            # CRUD items (list/get/create/update/delete)
│       ├── reports.ts          # Report queries (monthly/daily/idol/type)
│       ├── idols.ts            # Idol entities tree
│       ├── types.ts            # Type categories
│       ├── users.ts            # User management
│       ├── backup.ts           # R2 backup/restore/download
│       └── export.ts           # Excel export/import (SheetJS)
└── public/                     # Static frontend → Cloudflare Pages
    ├── index.html
    ├── login.html
    ├── report.html
    ├── idols.html
    ├── types.html
    ├── users.html
    ├── backup.html
    └── assets/
        ├── app.js              # Shared JS logic
        └── style.css
```

---

## wrangler.toml ตัวอย่าง

```toml
name = "numa-log"
main = "src/index.ts"
compatibility_date = "2024-01-01"

[[d1_databases]]
binding = "DB"
database_name = "numa-log-db"
database_id = "<your-database-id>"

[[r2_buckets]]
binding = "BACKUPS"
bucket_name = "numa-log-backups"

[vars]
APP_VERSION = "2.0.0"

# Secrets (set via: wrangler secret put JWT_SECRET)
# JWT_SECRET
```

---

## Routing ใน Worker

```typescript
// src/index.ts
import { Hono } from 'hono'
import { itemsRouter } from './routes/items'
import { authRouter } from './routes/auth'

const app = new Hono<{ Bindings: Env }>()

app.route('/api/items', itemsRouter)
app.route('/api/auth', authRouter)
// ...

export default app
```

> แนะนำใช้ **[Hono](https://hono.dev/)** — lightweight router ที่ออกแบบมาสำหรับ Cloudflare Workers โดยเฉพาะ

---

## สรุปความยาก

| งาน | ความยาก | เหตุผล |
|-----|---------|--------|
| Rewrite PHP pages เป็น static HTML+JS | สูง | logic เยอะ, มี inline PHP ทุกไฟล์ |
| Auth (session → JWT) | กลาง | ต้อง design ใหม่ทั้งหมด |
| Excel import (PhpSpreadsheet → SheetJS) | กลาง | API ต่างกัน แต่ concept เหมือนกัน |
| Database migration (schema) | ต่ำ | D1 เป็น SQLite schema เหมือนกันทุก table |
| Backup system (disk → R2) | ต่ำ | concept เหมือนกัน แค่เปลี่ยน API |
| Security headers | ต่ำ | copy logic เดิมมาใส่ใน Response headers |

---

## Cloudflare Free Tier Limits (ข้อควรรู้)

| Service | Free Limit |
|---------|-----------|
| Workers | 100,000 requests/วัน, 10ms CPU/request |
| D1 | 5 million rows read/วัน, 100,000 writes/วัน, 5GB storage |
| R2 | 10GB storage, 1M Class A ops/เดือน |
| Pages | Unlimited static hosting |

---

## ลำดับการทำงานที่แนะนำ

1. **Setup project** — สร้าง Hono Worker project + wrangler.toml
2. **D1 migration** — สร้าง schema + migrate ข้อมูลจาก SQLite เดิม
3. **Auth** — Implement JWT login/logout
4. **API routes** — Port logic จาก `api.php` ทีละ action
5. **Static frontend** — แปลง PHP pages เป็น HTML+JS ทีละหน้า
6. **Backup/Export** — R2 + SheetJS
7. **Deploy** — `wrangler deploy` + Cloudflare Pages
