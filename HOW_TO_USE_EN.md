# Numa Log - How to Use

Numa Log is a web application for recording and analyzing idol merchandise purchase data. Built with PHP 8.2, SQLite, Bootstrap 5, and Chart.js. The UI is available in **English** and **Thai** — toggle EN / TH in the top-right corner of any page (the choice is remembered).

> This guide covers **v1.13.0**. The same content is available in the app under **Help**.

---

## Table of Contents

1. [Installation](#1-installation)
2. [Login](#2-login)
3. [Dashboard](#3-dashboard-indexphp)
4. [Item Management](#4-item-management-itemsphp)
5. [Reports](#5-reports-reportphp)
6. [Budgets](#6-budgets-budgetphp)
7. [Events](#7-events-eventsphp)
8. [Idol Management](#8-idol-management-idolsphp)
9. [Type Management & Item Presets](#9-type-management--item-presets-typesphp)
10. [User Management](#10-user-management-usersphp)
11. [Backup & Restore](#11-backup--restore-backupphp)
12. [Excel Import & Export](#12-excel-import--export)
13. [Configuration](#13-configuration)

---

## 1. Installation

### Option A: Docker (Recommended)

```bash
# Quick start
docker compose up -d

# Open browser at
http://localhost:8080
```

A pre-built image is also published at `ghcr.io/fordantitrust/numa-log:latest` — see [INSTALL.md](INSTALL.md).

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

The SQLite database is created automatically on first load, and schema upgrades are applied automatically after you update the code (a backup is taken first).

---

## 2. Login

### Default Credentials

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin` | Admin |

> **Important:** When logging in with the default `admin` password, the system will immediately force you to change it before accessing the application (new password must be at least 12 characters). After login you land on the **Dashboard**.

> **Brute-force protection:** After 5 failed login attempts within 15 minutes, the IP address is temporarily blocked for 15 minutes.

### Role Permissions

| Feature | Admin | User |
|---------|:-----:|:----:|
| View/Add/Edit/Delete items | ✅ | ✅ |
| View dashboard & reports | ✅ | ✅ |
| Manage budgets | ✅ | ✅ |
| Manage events | ✅ | ✅ |
| Manage idol data | ✅ | ✅ |
| Manage item types & presets | ✅ | ✅ |
| Change own password | ✅ | ✅ |
| Export Excel data | ✅ | ✅ |
| Import Excel data | ✅ | ❌ |
| Backup/Restore data | ✅ | ❌ |
| Manage users | ✅ | ❌ |
| Re-seed idol data | ✅ | ❌ |

---

## 3. Dashboard (index.php)

The landing page after login — an at-a-glance summary:

- **KPI cards** — total spent, items / quantity, average per month, and the latest month with its month-over-month change
- **Monthly trend** bar chart, **Top members** and **Top groups** lists
- **Type** and **Company** doughnut charts
- **Budget card** — this month's budget progress
- **Period selector** — *All time*, *Last 12 months*, or a specific year

If any type is marked *Exclude from reports*, an "excl. ฿X" note under the total shows how much was left out.

---

## 4. Item Management (items.php)

The page for recording all idol merchandise purchases.

### Add New Item

1. Click **"Add Item"** in the Filters card header
2. (Optional) pick a **Preset** at the top of the form — it fills in the type and price (and the title, if Title is still empty). See [Item Presets](#item-presets)
3. Fill in the form:
   - **Order Date** - Purchase date
   - **Event** - Pick a named event (searchable) — auto-fills Event Date. See [Events](#7-events-eventsphp)
   - **Event Date** - Event date (if applicable)
   - **Title** - Item name
   - **Idol** - Idol/group name (type to search from dropdown)
   - **Type** - Item type (type to search from dropdown)
   - **Price per Qty** - Price per unit
   - **Qty** - Quantity
4. Click **"Save"**

> The Idol and Type fields accept a brand-new name too. If the idol name matches **more than one** member (e.g. "Yuna" in ITZY and AKB48), the form shows candidate buttons — pick the right one and the save retries automatically.

### Edit / Clone / Delete

- **Edit** — click the pencil icon, change the data, click **Save** (the Preset dropdown is hidden when editing)
- **Clone** — click the copy icon to duplicate an item (including its linked event). If a "From" date filter is active, the new item's date is pre-filled from it
- **Delete** — click the trash icon and confirm

### Bulk Actions

Tick the checkbox on one or more rows (or the header checkbox for the whole page) and a bulk action bar appears:

| Action | Description |
|--------|-------------|
| **Assign to Event** | Link every selected item to one named event |
| **Unassign** | Clear the event link (each item keeps its own Event Date) |
| **Change Type** | Set the same type on every selected item |

> If you change items to a type marked *Exclude from reports*, those rows leave the list straight away — the page tells you and offers to switch on **Include excluded types**.

### Filtering and Searching

| Filter | Description |
|--------|-------------|
| **Idol** | Filter by one or more idols/groups |
| **Type** | Filter by one or more item types |
| **Event** | Filter by one or more events |
| **Filter by** | Whether the date range filters by Order Date or Event Date |
| **From / To** | Date range |
| **Search** | Search by item title |

- Multi-select filters: open the dropdown → type to search → select several values → they appear as badges (× to remove)
- **Click a row to filter quickly** — click an Order/Event Date to set "From" to that date; click an Idol, Type or Event badge to add it to the matching filter
- **Export** downloads the currently filtered data as Excel; **Clear** resets all filters
- **Include excluded types** toggle brings back types marked *Exclude from reports*
- Dropdowns refresh automatically when idols, types, events or presets are changed in another tab

### Sorting & Paging

- Click any column header to sort (click again to toggle ascending/descending)
- Page-size selector at the bottom-left: 20 (default) / 50 / 100 / 200

### Summary Cards

Total Items, Total Quantity and Total Spending — updated with the active filters.

---

## 5. Reports (report.php)

The reports page has **14 tabs**, grouped into dropdowns (**Overview** · **Over Time** · **Breakdown** · **More**). Data-heavy tabs load the first time you open them.

### Date Range

The **Period** bar above the tabs lets you view *All time*, *Last 12 months*, a year, or a custom range. Every tab follows it (by order date), and the choice is remembered in your browser. Two exceptions:

- **By Event / Event Summary** filter by **event date** — events overlapping the range, with all items linked to them, even ones ordered before the range
- **Inactive** uses only the range's end date, as an "as of" date

### Tabs

| Tab | Group | What it shows |
|-----|-------|---------------|
| **Overview** | — | KPI cards, monthly trend, Top 5 members, highlights, Type / Company doughnuts |
| **Monthly** | Over Time | Spending (bars) + quantity (line) by month. Click a month to drill down by day, with **Type** and **Idol** breakdowns; click a day to open the Items page filtered to it |
| **Trends** | Over Time | Cumulative spending, month-over-month growth bars, and a current-month forecast |
| **Seasonality** | Over Time | Spending by day of week and by month of year |
| **By Member** | Breakdown | Members ranked by spending. Click a name for type breakdown + monthly chart; click a month to open the filtered Items page |
| **Compare** | Breakdown | Two members side by side: summary cards, monthly line, by-type bars |
| **By Group** | Breakdown | Spending per group/unit (primary memberships). Click to see members |
| **By Unit** | Breakdown | Spending rolled up to unit entities, including sub-unit / project memberships |
| **By Company** | Breakdown | Spending per company. Click to see its groups |
| **By Type** | Breakdown | Types ranked by spending. Click a type for its member / group / company breakdown and monthly chart. **Combine types**: tick two or more types and press *View N types combined* |
| **By Event** | More | Spending per named event (links to the filtered Items list), unlinked items grouped by Event Date, and order→event lead time |
| **Event Summary** | More | Each event's date range, duration, Upcoming / Ongoing / Past status, spend and average per event-day |
| **Top Items** | More | Top 20 most expensive purchases, top 20 most frequent titles, avg / min / max unit price per type |
| **Inactive** | More | Members with no recent purchases (30 / 90 / 180 / 365 days) |

> If a type is marked *Exclude from reports*, a banner shows the excluded amount, and **Include excluded types** brings it back.

---

## 6. Budgets (budget.php)

Set monthly spending limits and track spending against them. Budgets are a **visual warning only** — they never block adding items.

### Scopes & Colours

Each budget is scoped **Overall**, **By Type**, **By Company**, **By Group** or **By Member**, with a limit (฿) and two thresholds:

- 🟢 Green below the yellow %
- 🟡 Yellow from the yellow % up to the red %
- 🔴 Red at or above the red % (over budget)

### Tabs

| Tab | Description |
|-----|-------------|
| **Insights** | Spending vs. budget over the last 6 / 12 / 24 months for one scope: KPI cards, spent-vs-limit chart, % used trend, and automatic recommendations |
| **Progress** | A progress bar for every budget in the selected month. Override a single month, or reset it to the recurring default |
| **Monthly Budgets** | A Scopes × Months grid for a whole year. Click the Default cell to edit the recurring budget, or a month cell to override it. Footer rows show Overall / Allocated / Unallocated |
| **Manage Budgets** | Create, edit and delete recurring defaults |

> A **Default** budget applies to every month; a **Custom** month override replaces it for that month only. Group / Company / Member budgets use the same membership-aware rules as the reports. A budget pointed at an excluded type still counts that type in full.

---

## 7. Events (events.php)

Name real events (concerts, fan meets, fan signs…) and link items to them.

### Create an Event

Click **Add Event** and enter:

- **Event Name** — e.g. "TWICE 5th World Tour Bangkok"
- **Start Date / End Date** — leave End Date empty for a single-day event
- **Description** — optional
- **Free entry** / **Did not attend** — optional flags for ticket detection

### Link Items

- **In the item form** — pick the event in the searchable **Event** field (auto-fills Event Date)
- **Auto-assign** — each event row shows how many unlinked items have an Event Date inside its range; one click links them all
- **Bulk assign** — on the Items page, tick items and click **Assign to Event**
- **Unassign** — the ✕ button next to the Event field, or **Unassign** in the bulk bar

### Ticket Detection

Each event shows a badge: **Ticket recorded**, **No ticket**, **Free entry** or **Did not attend**. This needs at least one type marked **Is Ticket Type** (see [Type Management](#9-type-management--item-presets-typesphp)).

> Deleting an event only unlinks its items — it never deletes them.

---

## 8. Idol Management (idols.php)

Manage the hierarchical structure of idols: **Company > Group/Unit > Member**

### Add Idol Entity

1. Click the **"Add Entity"** button
2. Fill in the details:
   - **Name** — Entity name (duplicates are allowed; see Display Hint below)
   - **Category** — `company`, `group`, `unit`, `member`
   - **Parent** — Parent entity (default group for a member)
   - **Display Hint** — Optional short label to tell same-name members apart (e.g. "ITZY", "AKB48 Team A"). Auto-suggested from the parent group when a name collision is detected
   - **Sort Order** — Display order
3. Click **"Save"** — for member entities, a default primary membership is created from the parent

### Memberships

Open a member's edit form to see the **Memberships** section:

- **Add membership** — link the member to another group with optional start/end dates
- **Move to new group** — closes the current membership the day before the move date and starts a new one. Items bought before the move stay with the old group
- **Primary vs. sub-unit** — primary memberships count in By-Group / By-Company reports; sub-unit ones show only in the drill-down
- **Overlap warnings** — overlapping primary periods are saved but warned

### Hierarchy Structure

```
Company
  └── Group / Unit
        └── Member  (linked to its group(s) via idol_memberships)
```

### Unmapped Names

Idol names used in items but not yet in the tree are listed with a **Quick Add** button. After you create the entity, matching items are linked automatically.

### Ambiguous Mappings

Items whose idol name matches **more than one** member are listed in the **Ambiguous Mappings** panel (with a banner on the Items page). Click **Resolve Conflicts** to bulk-remap each name to the correct entity.

### Statistics

Each entity shows its item count and total spending; 🔄 marks members with more than one membership; members with a Display Hint render as `Name [hint]`.

---

## 9. Type Management & Item Presets (types.php)

### Add Item Type

1. Click the **"Add Type"** button
2. Fill in the details:
   - **Name** - Type name (e.g., Photocard, Cheki, Lightstick)
   - **Description** - Description
   - **Sort Order** - Display order
   - **Is Ticket Type** - items of this type count as an event ticket
   - **Exclude from reports** - leave this type out of normal totals
3. Click **"Save"**

### Exclude from Reports

For spending you want on record but not mixed in with your merch — travel, gifts for other people, items to resell. Items of a flagged type are left out of the dashboard, all report tabs, budgets, the item list and Excel export.

- Nothing is hidden silently — the excluded amount is always shown, and every page has an **Include excluded types** toggle
- Exceptions: a budget pointed directly at the type counts it in full, and the Events page still counts it for ticket detection
- **Renaming a type** leaves existing items on the old name (so they stop being excluded). Numa Log detects this and offers to update them

### Item Presets

For things you buy repeatedly at the same price (e.g. *Cheki ฿300*, *Deco Cheki* → type Pin Cheki ฿500). In the **Item Presets** card, add a button label, a type, a price and an optional title.

- The Add Item / Clone form has a **Preset** dropdown — it fills type and price, and the title only if Title is empty
- The price stays editable; a "changed from preset" note appears when it differs
- Items are not linked to presets — editing or deleting a preset never changes saved items or past reports

### Usage Statistics, Unmapped Names, Members by Type

- Each type shows row count, total quantity and total spending
- Type names used in items but not yet created are listed with **Quick Add**
- The **Members by Type** accordion shows which members, groups and companies bought each type

---

## 10. User Management (users.php)

> Admin only (except changing own password)

### Create New User

1. Click the **"Add User"** button
2. Fill in **Username**, **Password** (minimum **12 characters**), **Display Name** and **Role** (`admin` or `user`)
3. Click **"Save"**

### Change Password

All users can change their own password with the **"Change Password"** button. The new password must be at least **12 characters**.

---

## 11. Backup & Restore (backup.php)

> Admin only

- **Create Backup** — optionally enter a label, then click **"Create Backup"**
- **Restore** — select a backup, click **"Restore"** and confirm (an automatic backup is created first)
- **Download / Upload** — move backup files to and from your computer (e.g. to migrate to another machine)
- **Delete** — remove backups you no longer need

> Before each schema upgrade, the system also writes a `pre-v{N}-…` snapshot to the backups folder.

---

## 12. Excel Import & Export

### Export Excel

1. Apply filters on the Items page if you want a specific subset
2. Click **"Export"** in the Filters card header
3. The `.xlsx` file downloads immediately (filename reflects the active date range)

| Column | Field |
|--------|-------|
| A | Order Date |
| B | Event Date |
| C | Title |
| D | Idol |
| E | Type |
| F | Price per Qty |
| G | Qty |
| H | Price Total (F × G) |

> Export is available to all users. Excluded types are left out unless **Include excluded types** is on or you filtered by that type.

### Import Excel

> Admin only | Must be enabled in config first

Enable in `config.php`:

```php
define('ALLOW_IMPORT', true);
```

1. Prepare an `.xlsx` file with columns: Order Date, Event Date, Title, Idol, Type, Price per Qty, Qty
2. Click **"Import Excel"** on the Items page
3. Select the file and confirm

> **Warning:** Importing will **delete all existing data** before importing new data. Always create a backup first!

---

## 13. Configuration

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
| Excel Import / Export | PhpSpreadsheet 5.10 |
| Database | SQLite (WAL mode), schema v13 |
| Container | Docker & Docker Compose |
