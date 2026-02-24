# Project Structure

```
numa-log/
├── .github/
│   └── workflows/
│       └── docker-build.yml  # GitHub Actions: build Docker image
├── config.php                # Database connection, schema, auth helpers
├── index.php                 # Main item list (CRUD)
├── api.php                   # REST API for items, reports, idols, types, backups
├── api_users.php             # REST API for user management
├── report.php                # Reports page (Monthly, Member, Group, Company, Type)
├── idols.php                 # Idol hierarchy management
├── types.php                 # Type category management
├── users.php                 # User management
├── login.php                 # Login page
├── backup.php                # Backup & restore management
├── backup_upload.php         # Backup file upload handler
├── import.php                # Excel to SQLite importer
├── seed_idols.php            # Idol entity seeder
├── help.php                  # Help & guide page (Thai)
├── help_en.php               # Help & guide page (English)
├── HOW_TO_USE.md             # How to use documentation (Thai)
├── HOW_TO_USE_EN.md          # How to use documentation (English)
├── INSTALL.md                # Installation & upgrade guide
├── CHANGELOG.md              # Version history
├── Dockerfile                # Docker image definition
├── docker-compose.yml        # Docker Compose configuration
├── .dockerignore             # Docker build exclusions
├── .gitignore                # Git ignored files
├── composer.json             # Composer dependencies
├── database.sqlite           # SQLite database (auto-created)
├── data/                     # Persistent data directory (Docker)
│   ├── database.sqlite
│   └── backups/
└── backups/                  # Backup snapshots directory (manual)
    └── .htaccess             # Deny direct access
```
