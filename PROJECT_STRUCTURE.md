# Project Structure

```
numa-log/
├── .github/
│   ├── FUNDING.yml               # GitHub Sponsors link
│   └── workflows/
│       └── docker-build.yml      # GitHub Actions: build & publish Docker image to GHCR
│
├── config.php                    # Constants, getDB(), initDB() + migrations, auth, CSRF, i18n helpers
├── navbar.php                    # Shared top navbar partial (included by every page)
├── helpers_idol.php              # Idol-resolution helpers (ambiguous name detection, display hints)
├── helpers_report.php            # Report helpers: exclude-from-reports clauses, report date-range clauses
│
├── index.php                     # Dashboard landing page (KPIs, trend, top lists, doughnuts, budget card)
├── items.php                     # Item list CRUD, filters, bulk actions, item presets
├── report.php                    # Reports page (14 tabs in dropdowns + period/date-range bar)
├── budget.php                    # Budgets & spending goals (Insights / Progress / Monthly / Manage)
├── events.php                    # Named events, auto/bulk assign, ticket detection
├── idols.php                     # Idol entity tree + membership management
├── types.php                     # Type categories (is_ticket, exclude_from_reports) + item presets card
├── users.php                     # User management (admin only)
├── backup.php                    # Backup & restore management (admin only)
├── backup_upload.php             # Backup file upload handler
├── login.php                     # Login page (brute-force protected)
├── change_password_required.php  # Forced first-login password change
├── help.php                      # In-app help & guide (Thai)
├── help_en.php                   # In-app help & guide (English)
│
├── api.php                       # REST API — items, reports, idols, types, presets, events, budgets, backups
├── api_users.php                 # REST API — user management
├── export.php                    # SQLite → Excel exporter
├── import.php                    # Excel → SQLite importer (requires ALLOW_IMPORT)
├── seed_idols.php                # Idol entity seeder (requires ALLOW_RESEED)
├── migrate.php                   # CLI-only migration trigger / schema version check
├── example.idols.xlsx            # Sample idol data for import
│
├── assets/
│   ├── i18n.js                   # Browser-side t() helper (reads window.I18N)
│   ├── budget.js                 # Shared budget progress-bar / insights rendering
│   └── sync.js                   # Cross-tab data sync (BroadcastChannel + localStorage fallback)
│
├── lang/
│   ├── en.php                    # Base translation strings (English)
│   └── th.php                    # Thai overlay (missing keys fall back to English)
│
├── migrations/
│   ├── README.md                 # How migrations work
│   ├── _helpers.php              # getSchemaVersion, setSchemaVersion, autoBackupBeforeMigration
│   ├── v5_idol_refactor.php      # Membership history + ID-based idol reference
│   ├── v6_budgets.php            # budgets table
│   ├── v7_budget_periods.php     # budgets.period (per-month overrides)
│   ├── v8_events.php             # events table + items.event_id
│   ├── v9_event_end_date.php     # events.end_date (multi-day events)
│   ├── v10_ticket_tracking.php   # type_categories.is_ticket + events.is_free_entry
│   ├── v11_event_not_attended.php # events.is_not_attended
│   ├── v12_exclude_from_reports.php # type_categories.exclude_from_reports
│   └── v13_item_presets.php      # item_presets table
│
├── tests/
│   ├── run.php                   # Integration test suite (php tests/run.php)
│   ├── migration_test.php        # Migration tests
│   └── prepend.php               # Overrides DB_PATH for the isolated test database
│
├── CLAUDE.md                     # Project context for Claude Code
├── SKILL.md                      # Dev workflow procedures (Thai)
├── README.md                     # Overview & feature list
├── INSTALL.md                    # Installation & upgrade guide
├── HOW_TO_USE.md                 # How to use (Thai)
├── HOW_TO_USE_EN.md              # How to use (English)
├── API_ENDPOINTS.md              # API reference
├── DATABASE_SCHEMA.md            # Table definitions
├── CHANGELOG.md                  # Version history
├── Docker.md                     # Dockerfile hardening notes (Thai)
├── BUDGET_FEATURE_PLAN.md        # Design notes for the budget feature (implemented, v1.8.0)
├── MEMBERSHIP_HISTORY_PLAN.md    # Design notes for the v5 idol refactor (implemented, v1.5.0)
├── CLOUDFLARE_MIGRATION.md       # Exploratory plan: Cloudflare serverless port (not implemented)
│
├── Dockerfile                    # Multi-stage Docker image definition
├── docker-compose.yml            # Docker Compose configuration
├── .dockerignore                 # Docker build exclusions
├── .htaccess                     # Apache rules
├── .gitignore
├── composer.json / composer.lock # Composer dependencies (phpoffice/phpspreadsheet)
├── LICENSE                       # MIT
│
├── database.sqlite               # SQLite database — manual install (auto-created)
├── data/                         # Persistent data directory (Docker)
│   ├── .htaccess                 # Deny direct access
│   ├── database.sqlite
│   └── backups/                  # Backups + pre-migration snapshots (pre-v{N}-*.sqlite)
└── backups/                      # Backup snapshots directory (manual install)
    └── .htaccess                 # Deny direct access
```
