# Dockerfile — v1.4.0

> สรุปการปรับปรุง Dockerfile ตามแนวทาง Docker Security Best Practices

---

## ภาพรวมการเปลี่ยนแปลง

| หัวข้อ | เดิม (≤ 1.3.9) | ใหม่ (1.3.10) |
|--------|---------------|--------------|
| Build strategy | Single-stage | **Multi-stage** (builder + runtime) |
| `git`, `unzip` ใน final image | มี | **ไม่มี** (build-time only) |
| Composer binary ใน final image | มี | **ไม่มี** |
| PHP security config | ไม่มี | **มี** (`security.ini`) |
| Apache security config | ไม่มี | **มี** (`hardening.conf`) |
| File permissions | `chmod -R 755/775` | **แยก file/directory** (644/755/775) |
| HEALTHCHECK | ไม่มี | **มี** |
| `--no-install-recommends` | ไม่มี | **มี** |

---

## Multi-stage Build

### Stage 1 — `builder`

```
FROM php:8.2-apache AS builder
```

- ติดตั้ง build dependencies ทั้งหมด รวมถึง `git`, `unzip`, และ `-dev` packages
- Compile PHP extensions: `pdo_sqlite`, `zip`, `gd`
- รัน `composer install --no-dev --optimize-autoloader --no-scripts`
- **ไม่มี** stage นี้ใน final image

### Stage 2 — `runtime`

```
FROM php:8.2-apache AS runtime
```

- ติดตั้งเฉพาะ runtime dependencies (`-dev` packages เพื่อความ compatible ข้าม Debian version)
- **ไม่มี** `git`, `unzip`, Composer
- Copy `.so` extension files จาก `builder` → enable ด้วย `docker-php-ext-enable`
- Copy `vendor/` จาก `builder`, copy source code จาก local

#### เหตุผลที่ใช้ `-dev` packages ใน runtime stage

Runtime library names เปลี่ยนตาม Debian release:
- Bookworm: `libzip4`
- Trixie: `libzip4t64`

การใช้ `-dev` packages แก้ปัญหา `exit code: 100` ได้ เพราะ apt จะ resolve runtime library ที่ถูกต้องโดยอัตโนมัติ

---

## PHP Security Hardening

ไฟล์: `/usr/local/etc/php/conf.d/security.ini`

| Setting | ค่า | เหตุผล |
|---------|-----|--------|
| `expose_php` | `Off` | ซ่อน PHP version จาก HTTP response header |
| `display_errors` | `Off` | ไม่แสดง error ใน browser (production) |
| `display_startup_errors` | `Off` | ซ่อน startup errors |
| `log_errors` | `On` | Log ไปยัง stderr (`/proc/self/fd/2`) ตาม Docker convention |
| `disable_functions` | `exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec` | บล็อก server-side command execution |
| `session.cookie_httponly` | `On` | ป้องกัน XSS ขโมย session cookie |
| `session.use_strict_mode` | `On` | ป้องกัน session fixation |
| `session.use_only_cookies` | `On` | ป้องกัน session ID ใน URL |
| `upload_max_filesize` | `50M` | คงเดิม |
| `post_max_size` | `50M` | คงเดิม |
| `memory_limit` | `256M` | คงเดิม |

---

## Apache Security Hardening

ไฟล์: `/etc/apache2/conf-available/hardening.conf`

| Setting | ค่า | เหตุผล |
|---------|-----|--------|
| `ServerTokens` | `Prod` | แสดงแค่ "Apache" ซ่อน OS และ version |
| `ServerSignature` | `Off` | ซ่อน server info จาก error pages |
| `TraceEnable` | `Off` | ปิด HTTP TRACE method (ป้องกัน XST attacks) |
| `X-Content-Type-Options` | `nosniff` | ป้องกัน MIME-type sniffing |
| `X-Frame-Options` | `SAMEORIGIN` | ป้องกัน clickjacking |
| `Referrer-Policy` | `no-referrer-when-downgrade` | จำกัด referrer information |

Apache modules เพิ่มเติม: `mod_rewrite`, `mod_headers`

---

## File Permissions

```
/var/www/html/
├── files        → 644  (owner: www-data)
├── directories  → 755  (owner: www-data)
└── data/        → 775  (writable สำหรับ SQLite + backups)
    └── backups/
```

- ใช้ `find` แทน `chmod -R` เพื่อแยก permission ของ file และ directory อย่างถูกต้อง
- `composer.json`, `composer.lock` → `640` (ไม่ให้ others อ่าน)
- Apache main process ต้อง start เป็น root เพื่อ bind port 80 แต่ worker processes ทำงานเป็น `www-data` (default behaviour)

---

## HEALTHCHECK

```dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -fsSo /dev/null http://localhost/ || exit 1
```

| Parameter | ค่า |
|-----------|-----|
| Interval | ทุก 30 วินาที |
| Timeout | 10 วินาที |
| Start period | รอ 30 วินาทีหลัง container start |
| Retries | 3 ครั้งก่อน mark เป็น unhealthy |

---

## .dockerignore (อัปเดต)

เพิ่ม exclusion:

```
.claude/          # IDE / Claude config
.vscode/
.idea/
Dockerfile        # ไม่ต้อง copy เข้า image
docker-compose*
tests/
*.log, *.tmp
*.xlsx, *.csv
vendor/           # ติดตั้งใหม่ใน builder stage
```

---

## Build Commands

```bash
# Build
docker build -t numa-log:1.3.10 .

# Run (mount data volume สำหรับ SQLite persistence)
docker run -d \
  -p 80:80 \
  -v numa-log-data:/var/www/html/data \
  --name numa-log \
  numa-log:1.3.10

# ตรวจสอบ health status
docker inspect --format='{{.State.Health.Status}}' numa-log
```
