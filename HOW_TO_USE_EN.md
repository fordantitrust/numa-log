# Numa Log - How to Use

Numa Log is a web application for recording and analyzing idol merchandise purchase data. Built with PHP 8.2, SQLite, Bootstrap 5, and Chart.js.

---

## Table of Contents

1. [Installation](#1-installation)
2. [Login](#2-login)
3. [Item Management](#3-item-management-indexphp)
4. [Reports](#4-reports-reportphp)
5. [Idol Management](#5-idol-management-idolsphp)
6. [Type Management](#6-type-management-typesphp)
7. [User Management](#7-user-management-usersphp)
8. [Backup & Restore](#8-backup--restore-backupphp)
9. [Excel Import](#9-excel-import)
10. [Configuration](#10-configuration)

---

## 1. Installation

### Option A: Docker (Recommended)

```bash
# Quick start
docker compose up -d

# Open browser at
http://localhost:8080
```

**Change Port:** Edit `docker-compose.yml` and change `ports: "8080:80"` to your desired port.

**Docker Management Commands:**

| Command | Description |
|---------|-------------|
| `docker compose up -d` | Start the application |
| `docker compose down` | Stop the application |
| `docker compose logs -f` | View logs |
| `docker compose up -d --build` | Rebuild after code changes |
| `docker compose down -v` | Delete everything including data (caution!) |

### Option B: Manual (XAMPP)

```bash
# 1. Clone the repository
git clone <repository-url>
cd numa-log

# 2. Install dependencies
composer install

# 3. Place the project in XAMPP's htdocs
# C:\xampp\htdocs\numa-log\

# 4. Open browser
http://localhost/numa-log/
```

The SQLite database will be created automatically on first load.

---

## 2. Login

### Default Credentials

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin` | Admin |

> **Important:** Change the default password immediately after first login via the Users page.

### Role Permissions

| Feature | Admin | User |
|---------|:-----:|:----:|
| View/Add/Edit/Delete items | ✅ | ✅ |
| View reports | ✅ | ✅ |
| Manage idol data | ✅ | ✅ |
| Manage item types | ✅ | ✅ |
| Change own password | ✅ | ✅ |
| Import Excel data | ✅ | ❌ |
| Backup/Restore data | ✅ | ❌ |
| Manage users | ✅ | ❌ |
| Re-seed idol data | ✅ | ❌ |

---

## 3. Item Management (index.php)

The main page for recording all idol merchandise purchases.

### Add New Item

1. Click the **"Add Item"** button at the top
2. Fill in the form:
   - **Order Date** - Purchase date
   - **Event Date** - Event date (if applicable)
   - **Title** - Item name
   - **Idol** - Idol/group name (type to search from dropdown)
   - **Type** - Item type (type to search from dropdown)
   - **Price per Qty** - Price per unit
   - **Qty** - Quantity
3. Click **"Save"**

### Edit Item

1. Click the **edit** button (pencil icon) on the item row
2. Modify the data in the form
3. Click **"Save"**

### Clone (Duplicate) Item

1. Click the **Clone** button (copy icon) on the item row
2. The system will create a new item with the same data
3. Modify as needed, then click **"Save"**

### Delete Item

1. Click the **delete** button (trash icon) on the item row
2. Confirm the deletion

### Filtering and Searching

Use the filters above the table to find items:

| Filter | Description |
|--------|-------------|
| **Idol** | Filter by idol/group name |
| **Type** | Filter by item type |
| **Date Range** | Filter by date range |
| **Search** | Search by item title |

### Sorting

- Click on any column header to sort (click again to toggle ascending/descending)

### Summary Cards

The top of the table displays a summary:
- **Total Items** - Number of items
- **Total Quantity** - Total quantity
- **Total Spending** - Total amount spent

---

## 4. Reports (report.php)

The reports page has 5 tabs, each showing data from a different perspective:

### 4.1 Monthly

- Bar chart (spending) + line chart (quantity) by month
- **Click on any month** to drill down to daily details

### 4.2 By Member

- Ranking of idol members by spending
- **Click on a member name** for details:
  - Breakdown by item type
  - Monthly spending chart

### 4.3 By Group

- Aggregated spending for each group/unit
- **Click to expand** and see member breakdown

### 4.4 By Company

- Aggregated spending for each company
- **Click to expand** and see groups under the company

### 4.5 By Type

- Ranking of item types by spending
- Shows item count and quantity for each type
- **Click on a type name** to see details:
  - Members, groups, and companies that purchased that type
  - Item count, quantity, total spending, and share percentage

---

## 5. Idol Management (idols.php)

Manage the hierarchical structure of idols: **Company > Group/Unit > Member**

### Add Idol Entity

1. Click the **"Add Entity"** button
2. Fill in the details:
   - **Name** - Entity name
   - **Category** - Type (`company`, `group`, `unit`, `member`)
   - **Parent** - Parent entity (e.g., which group a member belongs to)
   - **Sort Order** - Display order
3. Click **"Save"**

### Hierarchy Structure

```
Company
  └── Group / Unit
        └── Member
```

### Unmapped Names

- The system detects idol names in items that haven't been added to idol_entities
- Displayed as a list with a **Quick Add** button for fast entry

### Statistics

Each entity shows:
- Number of items
- Total spending

---

## 6. Type Management (types.php)

### Add Item Type

1. Click the **"Add Type"** button
2. Fill in the details:
   - **Name** - Type name (e.g., Photocard, T-Shirt, Lightstick)
   - **Description** - Description
   - **Sort Order** - Display order
3. Click **"Save"**

### Usage Statistics

Each type shows:
- Number of rows using this type
- Total quantity
- Total spending

### Unmapped Type Names

- The system detects type names in items that haven't been added to type_categories
- Displayed as a list with a **Quick Add** button

### Members by Type

The bottom of the Types page includes a **Members by Type** accordion:
- Expand each type to see which members purchased items of that type
- Shows the group/unit and company for each member
- Displays statistics: item count, quantity, and total spending

---

## 7. User Management (users.php)

> Admin only (except changing own password)

### Create New User

1. Click the **"Add User"** button
2. Fill in the details:
   - **Username** - Login username
   - **Password** - Password
   - **Display Name** - Display name
   - **Role** - Permission level (`admin` or `user`)
3. Click **"Save"**

### Change Password

- All users can change their own password
- Click the **"Change Password"** button next to your name

---

## 8. Backup & Restore (backup.php)

> Admin only

### Create Backup

1. Go to the **Backup** page
2. Enter a label name for the backup (optional)
3. Click **"Create Backup"**

### Restore Data

1. Select the backup to restore from
2. Click **"Restore"**
3. Confirm the restoration

> The system automatically creates a backup before every restore to prevent data loss.

### Download / Upload Backup

- **Download** - Download a backup file to your computer
- **Upload** - Upload a previously downloaded backup file back to the system

### Delete Backup

- Click **"Delete"** on the backup you want to remove

---

## 9. Excel Import

> Admin only | Must be enabled in config first

### Enable Import

Edit `config.php`:

```php
define('ALLOW_IMPORT', true);
```

### How to Import

1. Prepare an `.xlsx` file with columns: Order Date, Event Date, Title, Idol, Type, Price per Qty, Qty
2. Click the **"Import Excel"** button on the Items page
3. Select the `.xlsx` file
4. Confirm the import

> **Warning:** Importing will **delete all existing data** before importing new data. Always create a backup first!

---

## 10. Configuration

All settings are in `config.php`:

| Setting | Default | Description |
|---------|---------|-------------|
| `ALLOW_IMPORT` | `false` | Enable/disable Excel import button |
| `ALLOW_RESEED` | `false` | Enable/disable idol data re-seed button |
| `AUTH_ENABLED` | `true` | Enable/disable login system |
| `SESSION_LIFETIME` | `86400` | Session lifetime in seconds (default = 24 hours) |

### Disable Login (for personal/development use)

```php
define('AUTH_ENABLED', false);
```

### Enable Import and Re-seed

```php
define('ALLOW_IMPORT', true);   // Show Import Excel button
define('ALLOW_RESEED', true);   // Show Re-seed button on Idols page
```

---

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 8.2, PDO/SQLite |
| Frontend | Bootstrap 5.3.3, Bootstrap Icons 1.11.3 |
| Charts | Chart.js 4.4.7 |
| Excel Import | PhpSpreadsheet |
| Database | SQLite (WAL mode) |
| Container | Docker & Docker Compose |
